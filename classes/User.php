<?php
/**
 * User.php
 * -------------------------------------------------------
 * Handles authentication, registration, and session/
 * transaction logging for AeroFocus.
 * -------------------------------------------------------
 */

class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --------------------------------------------------
    // REGISTER
    // --------------------------------------------------
    public function register($username, $full_name, $email, $password, $occupation_type = 'Working Student')
    {
        // Check if username or email already exists
        $checkSql = "SELECT user_id FROM users WHERE username = :username OR email = :email LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already taken.'];
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, full_name, email, password_hash, occupation_type, role)
                VALUES (:username, :full_name, :email, :password_hash, :occupation_type, 'student')";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username',        $username);
            $stmt->bindParam(':full_name',       $full_name);
            $stmt->bindParam(':email',           $email);
            $stmt->bindParam(':password_hash',   $password_hash);
            $stmt->bindParam(':occupation_type', $occupation_type);
            $stmt->execute();

            $userId = (int)$this->conn->lastInsertId();
            $this->logTransaction($userId, 'REGISTER', 'user', $userId, "New account registered: {$username}");

            return ['success' => true, 'user_id' => $userId, 'username' => $username];
        } catch (PDOException $e) {
            error_log("register error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    // --------------------------------------------------
    // LOGIN
    // --------------------------------------------------
    public function login($username, $password)
    {
        $sql = "SELECT user_id, username, full_name, email, password_hash, role, is_active
                FROM users WHERE username = :username LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
            if (!(int)$user['is_active']) {
                return ['success' => false, 'message' => 'Your account has been deactivated.'];
            }
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Create a session token and log in the sessions table
            $token = bin2hex(random_bytes(32));
            $ip    = $_SERVER['REMOTE_ADDR']       ?? 'unknown';
            $ua    = $_SERVER['HTTP_USER_AGENT']   ?? 'unknown';

            $sessSql = "INSERT INTO sessions (user_id, session_token, ip_address, user_agent)
                        VALUES (:user_id, :token, :ip, :ua)";
            $sessStmt = $this->conn->prepare($sessSql);
            $sessStmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
            $sessStmt->bindParam(':token',   $token);
            $sessStmt->bindParam(':ip',      $ip);
            $sessStmt->bindParam(':ua',      $ua);
            $sessStmt->execute();

            $this->logTransaction($user['user_id'], 'LOGIN', 'user', $user['user_id'], "User logged in: {$user['username']}");

            return [
                'success'      => true,
                'user_id'      => $user['user_id'],
                'username'     => $user['username'],
                'full_name'    => $user['full_name'],
                'email'        => $user['email'],
                'role'         => $user['role'],
                'session_token'=> $token,
            ];
        } catch (PDOException $e) {
            error_log("login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    // --------------------------------------------------
    // LOGOUT
    // --------------------------------------------------
    public function logout($user_id, $session_token)
    {
        try {
            $sql = "UPDATE sessions SET is_active = 0, logged_out_at = NOW()
                    WHERE user_id = :uid AND session_token = :token";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':uid',   $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':token', $session_token);
            $stmt->execute();

            $this->logTransaction($user_id, 'LOGOUT', 'user', $user_id, "User logged out.");
            return true;
        } catch (PDOException $e) {
            error_log("logout error: " . $e->getMessage());
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
