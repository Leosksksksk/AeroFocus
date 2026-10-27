<?php
/**
 * User.php
 * -------------------------------------------------------
 * Handles authentication, registration, and session /
 * transaction logging for AeroFocus.
 *
 * PDO TRANSACTIONS implemented:
 *   Transaction 1 – register()  : INSERT user + log entry (atomic)
 *   Transaction 2 – login()     : INSERT session + log entry (atomic)
 *   Transaction 3 – logout()    : UPDATE session + log entry (atomic)
 *
 * SESSION HELPERS:
 *   startSession()      – safely starts / resumes a PHP session
 *   destroySession()    – clears and destroys the active session
 *   regenerateSession() – regenerates the session ID (anti-fixation)
 *   getSessionData()    – returns all current session data as an array
 * -------------------------------------------------------
 */

class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ==================================================
    // SESSION MANAGEMENT HELPERS
    // ==================================================

    /**
     * startSession()
     * Safely starts a PHP session if one is not already active.
     * Sets secure cookie parameters before starting.
     *
     * @return void
     */
    public static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Harden the session cookie
            session_set_cookie_params([
                'lifetime' => 0,           // Until browser closes
                'path'     => '/',
                'secure'   => false,       // Set true when using HTTPS
                'httponly' => true,        // JS cannot access the cookie
                'samesite' => 'Strict',    // CSRF protection
            ]);
            session_start();
        }
    }

    /**
     * destroySession()
     * Unsets all session variables and destroys the session cookie.
     *
     * @return void
     */
    public static function destroySession()
    {
        session_unset();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * regenerateSession()
     * Regenerates the session ID to prevent session-fixation attacks.
     * Preserves existing session data.
     *
     * @return void
     */
    public static function regenerateSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * getSessionData()
     * Returns a snapshot of all current session data.
     *
     * @return array
     */
    public static function getSessionData()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        return $_SESSION;
    }

    // ==================================================
    // TRANSACTION 1 – REGISTER
    // Atomically inserts the new user row AND its audit
    // log entry. If either step fails the whole operation
    // is rolled back so the DB stays consistent.
    // ==================================================
    public function register($username, $full_name, $email, $password, $occupation_type = 'Working Student')
    {
        // Pre-flight: check for duplicate username / email (outside the
        // transaction because we're only reading here).
        $checkSql  = "SELECT user_id FROM users WHERE username = :username OR email = :email LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email',    $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already taken.'];
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // ----- BEGIN TRANSACTION 1 -----
        $this->conn->beginTransaction();
        try {
            // Step 1a – Insert the new user
            $sql  = "INSERT INTO users (username, full_name, email, password_hash, occupation_type, role)
                     VALUES (:username, :full_name, :email, :password_hash, :occupation_type, 'student')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username',        $username);
            $stmt->bindParam(':full_name',       $full_name);
            $stmt->bindParam(':email',           $email);
            $stmt->bindParam(':password_hash',   $password_hash);
            $stmt->bindParam(':occupation_type', $occupation_type);
            $stmt->execute();

            $userId = (int)$this->conn->lastInsertId();

            // Step 1b – Write the audit-log entry (same transaction)
            $logSql  = "INSERT INTO transactions (user_id, action, entity_type, entity_id, description)
                        VALUES (:uid, 'REGISTER', 'user', :eid, :desc)";
            $logStmt = $this->conn->prepare($logSql);
            $logStmt->bindParam(':uid',  $userId, PDO::PARAM_INT);
            $logStmt->bindParam(':eid',  $userId, PDO::PARAM_INT);
            $desc = "New account registered: {$username}";
            $logStmt->bindParam(':desc', $desc);
            $logStmt->execute();

            // ----- COMMIT TRANSACTION 1 -----
            $this->conn->commit();

            return ['success' => true, 'user_id' => $userId, 'username' => $username];

        } catch (PDOException $e) {
            // ----- ROLLBACK TRANSACTION 1 -----
            $this->conn->rollBack();
            error_log("register transaction error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    // ==================================================
    // TRANSACTION 2 – LOGIN
    // Atomically inserts the session row AND the audit-log
    // entry. Credentials are verified before the transaction
    // opens (read-only, so no transaction needed there).
    // ==================================================
    public function login($username, $password)
    {
        // Pre-flight credential check (read-only, outside transaction)
        $sql = "SELECT user_id, username, full_name, email, password_hash, role, is_active
                FROM users WHERE username = :username LIMIT 1";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("login fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
        if (!(int)$user['is_active']) {
            return ['success' => false, 'message' => 'Your account has been deactivated.'];
        }
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Generate a cryptographically secure session token
        $token = bin2hex(random_bytes(32));
        $ip    = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
        $ua    = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // ----- BEGIN TRANSACTION 2 -----
        $this->conn->beginTransaction();
        try {
            // Step 2a – Create a session record
            $sessSql  = "INSERT INTO sessions (user_id, session_token, ip_address, user_agent)
                         VALUES (:user_id, :token, :ip, :ua)";
            $sessStmt = $this->conn->prepare($sessSql);
            $sessStmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
            $sessStmt->bindParam(':token',   $token);
            $sessStmt->bindParam(':ip',      $ip);
            $sessStmt->bindParam(':ua',      $ua);
            $sessStmt->execute();

            // Step 2b – Write the audit-log entry (same transaction)
            $logSql  = "INSERT INTO transactions (user_id, action, entity_type, entity_id, description)
                        VALUES (:uid, 'LOGIN', 'user', :eid, :desc)";
            $logStmt = $this->conn->prepare($logSql);
            $logStmt->bindParam(':uid',  $user['user_id'], PDO::PARAM_INT);
            $logStmt->bindParam(':eid',  $user['user_id'], PDO::PARAM_INT);
            $desc = "User logged in: {$user['username']}";
            $logStmt->bindParam(':desc', $desc);
            $logStmt->execute();

            // ----- COMMIT TRANSACTION 2 -----
            $this->conn->commit();

            return [
                'success'       => true,
                'user_id'       => $user['user_id'],
                'username'      => $user['username'],
                'full_name'     => $user['full_name'],
                'email'         => $user['email'],
                'role'          => $user['role'],
                'session_token' => $token,
            ];

        } catch (PDOException $e) {
            // ----- ROLLBACK TRANSACTION 2 -----
            $this->conn->rollBack();
            error_log("login transaction error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    // ==================================================
    // TRANSACTION 3 – LOGOUT
    // Atomically marks the session as inactive AND writes
    // the audit-log entry so both always succeed or fail
    // together.
    // ==================================================
    public function logout($user_id, $session_token)
    {
        // ----- BEGIN TRANSACTION 3 -----
        $this->conn->beginTransaction();
        try {
            // Step 3a – Deactivate the session record
            $sql  = "UPDATE sessions
                     SET    is_active = 0, logged_out_at = NOW()
                     WHERE  user_id = :uid AND session_token = :token";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':uid',   $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':token', $session_token);
            $stmt->execute();

            // Step 3b – Write the audit-log entry (same transaction)
            $logSql  = "INSERT INTO transactions (user_id, action, entity_type, entity_id, description)
                        VALUES (:uid, 'LOGOUT', 'user', :eid, 'User logged out.')";
            $logStmt = $this->conn->prepare($logSql);
            $logStmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
            $logStmt->bindParam(':eid', $user_id, PDO::PARAM_INT);
            $logStmt->execute();

            // ----- COMMIT TRANSACTION 3 -----
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            // ----- ROLLBACK TRANSACTION 3 -----
            $this->conn->rollBack();
            error_log("logout transaction error: " . $e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------
    // GET USER BY ID
    // --------------------------------------------------
    public function getUserById($user_id)
    {
        $sql = "SELECT user_id, username, full_name, email, occupation_type, role, created_at
                FROM users WHERE user_id = :uid LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --------------------------------------------------
    // LOG TRANSACTION  (utility used by all entity ops)
    // --------------------------------------------------
    public function logTransaction($user_id, $action, $entity_type, $entity_id, $description)
    {
        try {
            $sql = "INSERT INTO transactions (user_id, action, entity_type, entity_id, description)
                    VALUES (:uid, :action, :entity_type, :entity_id, :description)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':uid',         $user_id,     PDO::PARAM_INT);
            $stmt->bindParam(':action',      $action);
            $stmt->bindParam(':entity_type', $entity_type);
            $stmt->bindParam(':entity_id',   $entity_id,   PDO::PARAM_INT);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("logTransaction error: " . $e->getMessage());
        }
    }

    // --------------------------------------------------
    // GET RECENT TRANSACTIONS FOR A USER
    // --------------------------------------------------
    public function getTransactions($user_id, $limit = 20)
    {
        $sql = "SELECT * FROM transactions WHERE user_id = :uid ORDER BY performed_at DESC LIMIT :lim";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':lim', $limit,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
