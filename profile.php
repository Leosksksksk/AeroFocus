<?php
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$database = new Database();
$db = $database->connect();
$uid = (int) $currentUser['user_id'];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$types = ['In-School Working Student', 'Working Student', 'Full-time Student', 'Part-time Student'];
$errors = [];

// Load the current record
$stmt = $db->prepare('SELECT user_id, username, full_name, email, occupation_type, password_hash
                      FROM users WHERE user_id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: logout.php'); exit; }

$form = [
    'username'        => $user['username'],
    'full_name'       => $user['full_name'],
    'email'           => $user['email'],
    'occupation_type' => $user['occupation_type'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token. Please reload the page and try again.';
    }

    $form['username']        = trim($_POST['username'] ?? '');
    $form['full_name']       = trim($_POST['full_name'] ?? '');
    $form['email']           = trim($_POST['email'] ?? '');
    $form['occupation_type'] = trim($_POST['occupation_type'] ?? '');
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Same rules as register.php
    if ($form['username'] === '')            $errors[] = 'Username is required.';
    elseif (strlen($form['username']) < 4)   $errors[] = 'Username must be at least 4 characters.';
    if ($form['full_name'] === '')           $errors[] = 'Full name is required.';
    if ($form['email'] === '')               $errors[] = 'Email is required.';
    elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!in_array($form['occupation_type'], $types, true)) $form['occupation_type'] = $user['occupation_type'];

    // Username / email must not belong to another account
    if (!$errors) {
        $chk = $db->prepare('SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id <> ?');
        $chk->execute([$form['username'], $form['email'], $uid]);
        if ($chk->fetch()) $errors[] = 'That username or email is already used by another account.';
    }

    // Optional password change
    $newHash = null;
    if ($current !== '' || $new !== '' || $confirm !== '') {
        if (!password_verify($current, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 6)    $errors[] = 'New password must be at least 6 characters.';
        if ($new !== $confirm)   $errors[] = 'New passwords do not match.';
        if (!$errors) $newHash = password_hash($new, PASSWORD_DEFAULT);
    }

    if (!$errors) {
        try {
            if ($newHash) {
                $up = $db->prepare('UPDATE users SET username=?, full_name=?, email=?, occupation_type=?, password_hash=? WHERE user_id=?');
                $up->execute([$form['username'], $form['full_name'], $form['email'], $form['occupation_type'], $newHash, $uid]);
            } else {
                $up = $db->prepare('UPDATE users SET username=?, full_name=?, email=?, occupation_type=? WHERE user_id=?');
                $up->execute([$form['username'], $form['full_name'], $form['email'], $form['occupation_type'], $uid]);
            }

            // Activity log (never blocks the save if it fails)
            try {
                $desc = $newHash ? 'Updated profile and changed password' : 'Updated profile information';
                $log = $db->prepare("INSERT INTO transactions (user_id, action, entity_type, entity_id, description)
                                     VALUES (?, 'UPDATE', 'user', ?, ?)");
                $log->execute([$uid, $uid, $desc]);
            } catch (Exception $e) { /* ignore */ }

            if (isset($_SESSION['username'])) $_SESSION['username'] = $form['username'];

            // Redirect so the header shows the new name
            header('Location: profile.php?flash=' . urlencode('Profile updated successfully.'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Could not save your changes. Please try again.';
        }
    }
}

$flash = $_GET['flash'] ?? '';

require_once 'includes/header.php';
?>

<div class="panel" style="max-width:620px; margin:30px auto;">
    <h2>My Account</h2>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="profile.php" class="stacked-form" autocomplete="off">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required
               value="<?php echo htmlspecialchars($form['username']); ?>">

        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" required
               value="<?php echo htmlspecialchars($form['full_name']); ?>">

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required
               value="<?php echo htmlspecialchars($form['email']); ?>">

        <label for="occupation_type">Occupation Type</label>
        <select id="occupation_type" name="occupation_type">
            <?php foreach ($types as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>"
                    <?php echo $form['occupation_type'] === $t ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <h3 style="color:var(--orange); margin:28px 0 0;">Change Password <small style="color:var(--muted); font-weight:400;">(optional)</small></h3>

        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password">

        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" placeholder="Min. 6 chars" autocomplete="new-password">

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php" class="btn btn-warning">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>