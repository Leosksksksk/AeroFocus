<?php
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/Assignment.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->connect();
$assignment = new Assignment($db);
$userObj = new User($db);

// Step 2: user confirmed via POST -> actually delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;

    if ($id > 0 && $assignment->deleteAssignment($id)) {
        $userObj->logTransaction($currentUser['user_id'], 'DELETE', 'assignment', $id, "Deleted assignment ID: {$id}");
        header("Location: index.php?flash=" . urlencode("Assignment deleted.") . "&flash_type=success");
    } else {
        header("Location: index.php?flash=" . urlencode("Unable to delete assignment.") . "&flash_type=error");
    }
    exit;
}

// Step 1: user clicked "Delete" -> GET shows a confirmation screen (no JS confirm dialog needed)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$record = $id > 0 ? $assignment->getAssignmentById($id) : false;

if (!$record) {
    header("Location: index.php?flash=" . urlencode("Assignment not found.") . "&flash_type=error");
    exit;
}

require_once 'includes/header.php';
?>

<div class="panel">
    <h2>Delete Assignment</h2>

    <div class="alert alert-error">
        Are you sure you want to delete <strong><?php echo htmlspecialchars($record['title']); ?></strong>
        (due <?php echo htmlspecialchars($record['due_date']); ?>)? This action cannot be undone.
    </div>

    <form action="delete_assignment.php" method="POST">
        <input type="hidden" name="assignment_id" value="<?php echo (int)$record['assignment_id']; ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Yes, Delete It</button>
            <a href="index.php" class="btn btn-grey">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
