<?php if (!defined('AEROFOCUS')) { exit('No direct access.'); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AeroFocus | Assignment Manager</title>
    <meta name="description" content="AeroFocus — Assignment and task tracker built for in-school working students.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="app-header">
    <div>
        <h1>AeroFocus</h1>
        <div class="tagline">Assignment Tracker for In-School Working Students</div>
    </div>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="subjects.php">Subjects</a>
        <a href="add_assignment.php">+ Assignment</a>
        <a href="transactions.php">Activity Log</a>
        <?php if (!empty($currentUser)): ?>
            <a href="profile.php" class="nav-user" title="Edit my account">
            <svg style="width:14px;height:14px;vertical-align:middle;margin-right:4px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <?php echo htmlspecialchars($currentUser['username']); ?>
        </a>
                    <a href="logout.php" class="nav-logout">Sign Out</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">