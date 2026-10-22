<?php
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/Assignment.php';
require_once 'classes/Note.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->connect();
$assignment = new Assignment($db);
$noteObj = new Note($db);
$userObj = new User($db);

$notes = $noteObj->getNotesByUser($currentUser['user_id']);

// Handle search via GET (plain HTML form, no JS)
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if ($keyword !== '') {
    $records = $assignment->searchAssignments($keyword, $currentUser['user_id']);
} else {
    $records = $assignment->getAssignments($currentUser['user_id']);
}

// Flash message passed via redirect query string
$flash = isset($_GET['flash']) ? $_GET['flash'] : '';
$flashType = isset($_GET['flash_type']) ? $_GET['flash_type'] : 'success';

require_once 'includes/header.php';
?>

<div class="panel">
    <h2>Assignment Dashboard</h2>

    <a class="btn btn-orange" href="subjects.php">Manage Subjects</a>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <form class="search-bar" action="index.php" method="GET">
        <input type="search" name="keyword" placeholder="Search by title, description, subject, priority, or status..."
               value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($keyword !== ''): ?>
            <a href="index.php" class="btn btn-grey">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (count($records) === 0): ?>
        <div class="empty-state">
            <?php if ($keyword !== ''): ?>
                No assignments matched "<?php echo htmlspecialchars($keyword); ?>".
            <?php else: ?>
                No assignments yet. Click "Add Assignment" to create your first one.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                            <span style="color:var(--grey-text); font-size:0.8rem;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['subject_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                        <td>
                            <span class="badge priority-<?php echo htmlspecialchars($row['priority']); ?>">
                                <?php echo htmlspecialchars($row['priority']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge status-<?php echo htmlspecialchars(str_replace(' ', '', $row['status'])); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <a class="btn btn-warning" href="edit_assignment.php?id=<?php echo (int)$row['assignment_id']; ?>">Edit</a>

                            <a class="btn btn-danger" href="delete_assignment.php?id=<?php echo (int)$row['assignment_id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Quick Notes & Scratchpad Panel -->
<div class="panel notes-panel" style="margin-top: 30px; background: #141824; border: 1px solid #1e293b; border-radius: 12px; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="color: #38bdf8; margin: 0; font-size: 20px; font-weight: 700;">Quick Notes & Scratchpad</h3>
        <small style="color: #64748b; font-size: 13px;">Fast reminders for work & classes</small>
    </div>

    <!-- Manual Input Form -->
    <form action="add_note.php" method="POST" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
            <!-- 1. Text Input for Note Title -->
            <input type="text" 
                   name="note_title" 
                   placeholder="Type your note here (e.g., Talk to groupmates about Cisco config)..." 
                   required 
                   style="flex: 2; min-width: 250px; padding: 12px 16px; background: #0f1219; border: 1px solid #334155; color: #ffffff; border-radius: 8px; font-size: 14px; outline: none;">
            
            <!-- 2. Tag Dropdown -->
            <select name="tag" 
                    style="flex: 1; min-width: 150px; padding: 12px 16px; background: #0f1219; border: 1px solid #334155; color: #ffffff; border-radius: 8px; font-size: 14px; outline: none; cursor: pointer;">
                <option value="quick">Quick Reminder</option>
                <option value="academic">Academic</option>
                <option value="work">Work Duty</option>
                <option value="urgent">Urgent</option>
            </select>

            <!-- 3. Save Button -->
            <button type="submit" 
                    style="padding: 12px 24px; background: #0284c7; color: #ffffff; border: 1px solid #38bdf8; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;">
                + Save Note
            </button>
        </div>
    </form>

    <!-- Notes List Display -->
    <div class="notes-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 20px;">
        <?php if (!empty($notes)): ?>
            <?php foreach ($notes as $note): ?>
                <div style="background: #171b26; border: 1px solid #1e293b; padding: 16px; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <strong style="color: #f8fafc; font-size: 15px;"><?php echo htmlspecialchars($note['title']); ?></strong>
                            <span style="font-size: 10px; padding: 2px 8px; border-radius: 4px; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid #38bdf8; font-weight: 600;">
                                <?php echo strtoupper(htmlspecialchars($note['tag'])); ?>
                            </span>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #1e293b; padding-top: 8px; margin-top: 8px;">
                        <small style="color: #64748b; font-size: 11px;"><?php echo htmlspecialchars($note['created_at']); ?></small>
                        <a href="delete_note.php?id=<?php echo (int)$note['id']; ?>" style="color: #ef4444; font-size: 12px; text-decoration: none; font-weight: 500;">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #64748b; font-size: 13px; grid-column: 1 / -1; margin: 0;">No active quick notes in scratchpad.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>