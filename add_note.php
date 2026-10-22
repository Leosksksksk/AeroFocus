<?php
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/Note.php';
require_once 'classes/User.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db       = $database->connect();
    $note     = new Note($db);
    $userObj  = new User($db);

    $title = isset($_POST['note_title']) ? trim($_POST['note_title']) : '';
    $tag   = isset($_POST['tag'])        ? trim($_POST['tag'])        : 'quick';

    if (!empty($title)) {
        if ($note->addNote($title, $tag, $currentUser['user_id'])) {
            $newId = (int)$db->lastInsertId();
            $userObj->logTransaction($currentUser['user_id'], 'CREATE', 'note', $newId, "Created note: {$title}");
        }
    }
}

header('Location: index.php?flash=Note+saved+successfully&flash_type=success');
exit();