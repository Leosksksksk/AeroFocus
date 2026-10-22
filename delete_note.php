<?php
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/Note.php';
require_once 'classes/User.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $database = new Database();
    $db       = $database->connect();
    $note     = new Note($db);
    $userObj  = new User($db);
    $noteId   = (int)$_GET['id'];

    $note->deleteNote($noteId);
    $userObj->logTransaction($currentUser['user_id'], 'DELETE', 'note', $noteId, "Deleted note ID: {$noteId}");
}

header('Location: index.php?flash=Note+deleted&flash_type=success');
exit();