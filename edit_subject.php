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

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$subject = $id > 0 ? $subjectObj->getSubjectById($id) : false;

if (!$subject) {
    header("Location: subjects.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $units          = trim($_POST['Units']          ?? '');
    $room           = trim($_POST['Room']           ?? '');
    $subjectName    = trim($_POST['SubjectName']    ?? '');
    $instructorName = trim($_POST['InstructorName'] ?? '');

    if (!empty($subjectName)) {
        if ($subjectObj->updateSubject($id, $units, $room, $subjectName, $instructorName)) {
            $userObj->logTransaction($currentUser['user_id'], 'UPDATE', 'subject', $id, "Updated subject: {$subjectName}");
            header("Location: subjects.php");
            exit();
        } else {
            $message = "Error updating subject.";
        }
    } else {
        $message = "Subject Name is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject - AeroFocus</title>
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

<div class="container" style="padding-top:40px;">
<div class="panel" style="border-radius:12px; border:1px solid #252836; max-width:520px; margin:auto;">
    <h2>Edit Subject</h2>
    <?php if ($message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_subject.php?id=<?= $subject['subject_id'] ?>" class="stacked-form">
        <label for="edit_units">Units</label>
        <input type="text" id="edit_units" name="Units" value="<?= htmlspecialchars($subject['units'] ?? '') ?>">

        <label for="edit_room">Room</label>
        <input type="text" id="edit_room" name="Room" value="<?= htmlspecialchars($subject['room'] ?? '') ?>">

        <label for="edit_name">Subject Name <span style="color:var(--red)">*</span></label>
        <input type="text" id="edit_name" name="SubjectName" required
               value="<?= htmlspecialchars($subject['subject_name']) ?>">

        <label for="edit_instructor">Instructor Name</label>
        <input type="text" id="edit_instructor" name="InstructorName"
               value="<?= htmlspecialchars($subject['instructor_name'] ?? '') ?>">

        <div class="form-actions">
            <button type="submit" class="btn btn-success">Update Subject</button>
            <a href="subjects.php" class="btn btn-grey">Cancel</a>
        </div>
    </form>
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