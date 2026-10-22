<?php
/**
 * logout.php – Destroys the session and logs the event.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'classes/User.php';

    $database = new Database();
    $db       = $database->connect();
    $userObj  = new User($db);

    $userObj->logout(
        $_SESSION['user_id'],
        $_SESSION['session_token'] ?? ''
    );
}

// Destroy session completely
session_unset();
session_destroy();

header('Location: login.php?flash=' . urlencode('You have been signed out successfully.'));
exit;
