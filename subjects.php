<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/Subject.php';
require_once 'classes/User.php';

$database   = new Database();
$db         = $database->connect();
$subjectObj = new Subject($db);
$userObj    = new User($db);

// Handle Delete
if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $subjectObj->deleteSubject($delId);
    $userObj->logTransaction($currentUser['user_id'], 'DELETE', 'subject', $delId, "Deleted subject ID: {$delId}");
    header("Location: subjects.php");
    exit();
}

$subjects = $subjectObj->getSubjectsByUser($currentUser['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AeroFocus - Manage Subjects</title>
    <meta name="description" content="Manage your subjects in AeroFocus.">
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
            <span class="nav-user">
                <svg style="width:14px;height:14px;vertical-align:middle;margin-right:4px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <?php echo htmlspecialchars($currentUser['username']); ?>
            </span>
            <a href="logout.php" class="nav-logout">Sign Out</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container" style="padding-top: 30px;">
<div class="panel" style="border-radius:12px; border:1px solid #4c8cedff; padding:28px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h2 style="margin:0;">Manage Subjects</h2>
        <a class="btn btn-orange" href="add_subject.php" style="color: #ffb75d !important; font-weight: 700;">+ Add New Subject</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Units</th>
                <th>Room</th>
                <th>Subject Name</th>
                <th>Instructor Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (is_array($subjects) && count($subjects) > 0): ?>
            <?php foreach ($subjects as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['units'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['room'] ?? '—') ?></td>
                <td><strong><?= htmlspecialchars($row['subject_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['instructor_name'] ?? '—') ?></td>
                <td class="actions-cell">
                    <a class="btn btn-warning" style="padding:5px 10px; font-size:0.8rem;" href="edit_subject.php?id=<?= $row['subject_id'] ?>">Edit</a>
                    <form method="POST" action="subjects.php" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= $row['subject_id'] ?>">
                        <button type="submit" class="btn btn-danger" style="padding:5px 10px; font-size:0.8rem;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="empty-state" style="padding:30px;">No subjects found. Add your first subject!</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<footer class="app-footer">
    AeroFocus &mdash; Built with PHP, PDO &amp; MySQL (XAMPP) | Developed by: <a href="https://www.facebook.com/Jnrl.hndsm.18" target="_blank" style="color:var(--blue); text-decoration:underline;">Junrel Alipogpog</a>
</footer>

<script src="js/math-bg.js"></script>
<script src="js/ui-sounds.js"></script>
<script src="js/bg-music.js"></script>
</body>
</html>
