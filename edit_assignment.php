<?php
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/Assignment.php';
require_once 'classes/Subject.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->connect();

$assignment = new Assignment($db);
$subjectHelper = new Subject($db);
$userObj = new User($db);
$subjects = $subjectHelper->getSubjectsByUser($currentUser['user_id']);

$errors = [];

// Determine which record we're editing
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0);

if ($id <= 0) {
    header("Location: index.php?flash=" . urlencode("Invalid assignment.") . "&flash_type=error");
    exit;
}

$record = $assignment->getAssignmentById($id);

if (!$record) {
    header("Location: index.php?flash=" . urlencode("Assignment not found.") . "&flash_type=error");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $subject_id = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;

    if ($title === '') { $errors[] = "Title is required."; }
    if ($due_date === '') { $errors[] = "Due date is required."; }
    if (!in_array($priority, ['Low', 'Medium', 'High'], true)) { $errors[] = "Invalid priority."; }
    if (!in_array($status, ['Pending', 'In Progress', 'Completed'], true)) { $errors[] = "Invalid status."; }

    if (empty($errors)) {
        $assignment->subject_id = $subject_id;
        $assignment->title = $title;
        $assignment->description = $description;
        $assignment->due_date = $due_date;
        $assignment->priority = $priority;
        $assignment->status = $status;

        if ($assignment->updateAssignment($id)) {
            $userObj->logTransaction($currentUser['user_id'], 'UPDATE', 'assignment', $id, "Updated assignment: {$title}");
            header("Location: index.php?flash=" . urlencode("Assignment updated successfully.") . "&flash_type=success");
            exit;
        } else {
            $errors[] = "Something went wrong while updating the assignment. Please try again.";
        }
    }

    // Keep the record array in sync with submitted values so the
    // form re-displays what the user typed if validation fails.
    $record = array_merge($record, $_POST);
}

require_once 'includes/header.php';
?>

<div class="panel">
    <h2>Edit Assignment</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error) { echo htmlspecialchars($error) . "<br>"; } ?>
        </div>
    <?php endif; ?>

    <form class="stacked-form" action="edit_assignment.php?id=<?php echo (int)$id; ?>" method="POST">
        <input type="hidden" name="assignment_id" value="<?php echo (int)$id; ?>">

        <label for="title">Title</label>
        <input type="text" id="title" name="title" required
               value="<?php echo htmlspecialchars($record['title']); ?>">

        <label for="subject_id">Subject</label>
        <select id="subject_id" name="subject_id">
            <option value="">-- None --</option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?php echo (int)$s['subject_id']; ?>"
                    <?php echo ($record['subject_id'] == $s['subject_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['subject_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description"><?php echo htmlspecialchars($record['description']); ?></textarea>

        <label for="due_date">Due Date</label>
        <input type="date" id="due_date" name="due_date" required
               value="<?php echo htmlspecialchars($record['due_date']); ?>"
               onclick="try{this.showPicker()}catch(e){}">

        <label for="priority">Priority</label>
        <select id="priority" name="priority" required>
            <?php foreach (['Low', 'Medium', 'High'] as $p): ?>
                <option value="<?php echo $p; ?>" <?php echo ($record['priority'] === $p) ? 'selected' : ''; ?>>
                    <?php echo $p; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <?php foreach (['Pending', 'In Progress', 'Completed'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo ($record['status'] === $s) ? 'selected' : ''; ?>>
                    <?php echo $s; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">Update Assignment</button>
            <a href="index.php" class="btn btn-grey">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
