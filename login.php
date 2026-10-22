<?php
/**
 * login.php – AeroFocus Authentication
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in? Redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'classes/User.php';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '') $errors[] = 'Username is required.';
    if ($password === '') $errors[] = 'Password is required.';

    if (empty($errors)) {
        $database = new Database();
        $db       = $database->connect();
        $userObj  = new User($db);
        $result   = $userObj->login($username, $password);

        if ($result['success']) {
            $_SESSION['user_id']       = $result['user_id'];
            $_SESSION['username']      = $result['username'];
            $_SESSION['full_name']     = $result['full_name'];
            $_SESSION['email']         = $result['email'];
            $_SESSION['role']          = $result['role'];
            $_SESSION['session_token'] = $result['session_token'];

            header('Location: index.php?flash=' . urlencode('Welcome back!, ' . $result['full_name'] . '!') . '&flash_type=success');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AeroFocus | Login</title>
    <meta name="description" content="Sign in to AeroFocus — the assignment tracker built for in-school working students.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:          #0d0f14;
            --panel:       #13161e;
            --panel-alt:   #1a1d28;
            --border:      #252836;
            --sky:         #38bdf8;
            --sky-glow:    rgba(56,189,248,.45);
            --orange:      #ff8c1a;
            --orange-glow: rgba(255,140,26,.45);
            --green:       #39ff8a;
            --green-glow:  rgba(57,255,138,.35);
            --red:         #ff3b4e;
            --red-glow:    rgba(255,59,78,.4);
            --text:        #e2e8f0;
            --muted:       #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image:
                radial-gradient(ellipse at 20% 20%, rgba(56,189,248,.07) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 80%, rgba(255,140,26,.07) 0%, transparent 55%);
            padding: 20px;
        }

        /* ---- Animated background grid ---- */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(56,189,248,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56,189,248,.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 1;
        }

        .auth-card {
            position: relative;
            z-index: 10;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px 44px;
            width: 100%;
            max-width: 440px;
            box-shadow:
                0 0 0 1px rgba(56,189,248,.08),
                0 25px 60px rgba(0,0,0,.5),
                0 0 80px rgba(56,189,248,.04);
            animation: fadeUp .4s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 36px;
        }
        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .brand-logo svg { width: 36px; height: 36px; }
        .brand h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--sky);
            text-shadow: 0 0 20px var(--sky-glow);
            letter-spacing: -0.5px;
        }
        .brand p {
            color: var(--orange);
            font-size: 13px;
            font-weight: 500;
            text-shadow: 0 0 10px var(--orange-glow);
            margin-top: 4px;
        }

        .form-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }
        .form-subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 28px;
        }

        .alert-error {
            background: rgba(255,59,78,.1);
            border: 1px solid var(--red);
            color: var(--red);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 20px;
            box-shadow: 0 0 15px var(--red-glow);
        }
        .alert-success {
            background: rgba(57,255,138,.08);
            border: 1px solid var(--green);
            color: var(--green);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--sky);
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap > svg.field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            color: var(--muted);
            pointer-events: none;
            z-index: 1;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color .2s, box-shadow .2s;
        }
        input[type="password"] {
            padding-right: 44px;
        }
        input:focus {
            outline: none;
            border-color: var(--sky);
            box-shadow: 0 0 0 3px rgba(56,189,248,.15);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
            color: #0d0f14;
            font-weight: 700;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 4px 20px rgba(56,189,248,.35);
            margin-top: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 30px rgba(56,189,248,.55);
        }
        .btn-submit:active { transform: translateY(0); }

        .divider {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin: 24px 0;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: var(--border);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .register-link {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }
        .register-link a {
            color: var(--sky);
            text-decoration: none;
            font-weight: 600;
            transition: opacity .15s;
        }
        .register-link a:hover { opacity: .8; }

        .show-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--muted);
            background: none;
            border: none;
            padding: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            z-index: 2;
            transition: color .2s, background-color .2s;
        }
        .show-pass:hover {
            color: var(--sky);
            background: rgba(56, 189, 248, 0.1);
        }
        .show-pass svg {
            position: static;
            transform: none;
            width: 18px;
            height: 18px;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <!-- Brand -->
    <div class="brand">
        <div class="brand-logo">
            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="18" cy="18" r="17" stroke="#38bdf8" stroke-width="2"/>
                <path d="M10 24 L18 10 L26 24" stroke="#ff8c1a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 20 H23" stroke="#38bdf8" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <h1>AeroFocus</h1>
        <p style="font-size: 12px;">Assignment Tracker for In-School Working Students</p>
    </div>

    <div class="form-title">Welcome, student!</div>
    <div class="form-subtitle">Sign in to continue to your dashboard</div>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Flash from register redirect -->
    <?php if (!empty($_GET['flash'])): ?>
        <div class="alert-success"><?php echo htmlspecialchars($_GET['flash']); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="login_username">Username</label>
            <div class="input-wrap">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <input type="text" id="login_username" name="username" placeholder="Enter your username"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="login_password">Password</label>
            <div class="input-wrap">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input type="password" id="login_password" name="password" placeholder="Enter your password" required>
                <button type="button" class="show-pass" onclick="togglePass('login_password', this)" aria-label="Show password" title="Show password">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit" id="login_submit">Sign In</button>
    </form>

    <div class="divider">or</div>

    <div class="register-link">
        Don't have an account? <a href="register.php">Create one</a>
    </div>
</div>
<div style="position: fixed; bottom: 20px; left: 20px; z-index: 1000; pointer-events: none;">
    <span style="color: #64748b; font-size: 12px; letter-spacing: 0.5px;">Developed by Junrel Alipogpog</span>
</div>

<script>
function togglePass(fieldId, btn) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    const isShowing = field.type === 'text';
    field.type = isShowing ? 'password' : 'text';

    if (btn) {
        if (isShowing) {
            btn.setAttribute('aria-label', 'Show password');
            btn.setAttribute('title', 'Show password');
            btn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>`;
        } else {
            btn.setAttribute('aria-label', 'Hide password');
            btn.setAttribute('title', 'Hide password');
            btn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                </svg>`;
        }
    }
}
</script>
<script src="js/math-bg.js"></script>
<script src="js/ui-sounds.js"></script>
<script src="js/bg-music.js"></script>
</body>
</html>
