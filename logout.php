<?php
/**
 * logout.php – Destroys the session and logs the event.
 *
 * Transaction 3 (logout) is handled inside User::logout().
 * After it commits, we call User::destroySession() which
 * properly expires the session cookie before wiping the
 * server-side session data.
 */
require_once 'config/database.php';
require_once 'classes/User.php';

// Start the session so we can read user_id / session_token
User::startSession();

if (!empty($_SESSION['user_id'])) {
    $database = new Database();
    $db       = $database->connect();
    $userObj  = new User($db);

    // Transaction 3: UPDATE sessions + INSERT transaction log (atomic)
    $userObj->logout(
        $_SESSION['user_id'],
        $_SESSION['session_token'] ?? ''
    );
}

// Clear and expire the session cookie, then destroy the session
User::destroySession();

header('Location: login.php?flash=' . urlencode('You have been signed out successfully.'));
exit;
