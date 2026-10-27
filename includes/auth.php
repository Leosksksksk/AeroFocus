<?php
/**
 * auth.php
 * -------------------------------------------------------
 * Session guard — include at the top of every protected
 * page. Redirects to login.php if the user is not signed
 * in. Also exposes the $currentUser array to the page.
 *
 * Uses User::startSession() so the session cookie is
 * always started with hardened parameters (HttpOnly,
 * SameSite=Strict, etc.).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../classes/User.php';

// Start (or resume) the PHP session with secure cookie settings
User::startSession();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Make current user data available to every protected page
$currentUser = [
    'user_id'   => $_SESSION['user_id'],
    'username'  => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role'      => $_SESSION['role'],
    'token'     => $_SESSION['session_token'] ?? '',
];
