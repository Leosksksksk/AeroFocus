<?php
/**
 * register.php – AeroFocus Registration
 *
 * Transaction 1 (register) is handled inside User::register().
 */
require_once 'config/database.php';
require_once 'classes/User.php';

// Start session with hardened cookie settings
User::startSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username']        ?? '');
    $full_name       = trim($_POST['full_name']       ?? '');
    $email           = trim($_POST['email']           ?? '');
    $occupation_type = trim($_POST['occupation_type'] ?? 'Working Student');
    $password        = $_POST['password']  ?? '';
    $confirm         = $_POST['confirm']   ?? '';

    if ($username === '')             $errors[] = 'Username is required.';
    elseif (strlen($username) < 4)   $errors[] = 'Username must be at least 4 characters.';
    if ($full_name === '')            $errors[] = 'Full name is required.';
    if ($email === '')                $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6)       $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)      $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $database = new Database();
        $db       = $database->connect();
        $userObj  = new User($db);
        $result   = $userObj->register($username, $full_name, $email, $password, $occupation_type);

        if ($result['success']) {
            header('Location: login.php?flash=' . urlencode('Account created! You can now sign in.'));
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
    <title>AeroFocus | Create Account</title>
    <meta name="description" content="Register a new AeroFocus account — the assignment tracker for in-school working students.">
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
            padding: 30px 20px;
        }
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
            max-width: 480px;
            box-shadow:
                0 0 0 1px rgba(255,140,26,.06),
                0 25px 60px rgba(0,0,0,.5),
                0 0 80px rgba(255,140,26,.04);
            animation: fadeUp .4s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand h1 {
            font-size: 26px; font-weight: 800;
            color: var(--sky);
            text-shadow: 0 0 20px var(--sky-glow);
        }
        .brand p { color: var(--orange); font-size: 13px; margin-top: 4px; }

        .form-title    { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
        .form-subtitle { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

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

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: 11px; font-weight: 600;
            color: var(--sky);
            text-transform: uppercase; letter-spacing: .7px;
            margin-bottom: 7px;
        }
        .input-wrap { position: relative; }
        .input-wrap svg.icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 15px; height: 15px;
            color: var(--muted); pointer-events: none;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 11px 12px 11px 40px;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color .2s, box-shadow .2s;
        }
        select { padding-left: 40px; cursor: pointer; }
        input:focus, select:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255,140,26,.15);
        }

        .show-pass {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer; color: var(--muted);
            background: none; border: none; padding: 6px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px;
            z-index: 2;
            transition: color .2s, background-color .2s;
        }
        .show-pass:hover {
            color: var(--orange);
            background: rgba(255, 140, 26, 0.1);
        }
        .show-pass svg {
            position: static;
            transform: none;
            width: 16px; height: 16px;
            pointer-events: none;
        }
        input[type="password"] {
            padding-right: 38px;
        }

        .password-hint {
            font-size: 11px; color: var(--muted); margin-top: 5px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ea580c, #ff8c1a);
            color: #0d0f14;
            font-weight: 700; font-size: 15px;
            font-family: 'Inter', sans-serif;
            border: none; border-radius: 10px;
            cursor: pointer; margin-top: 10px;
            transition: all .2s;
            box-shadow: 0 4px 20px rgba(255,140,26,.35);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 30px rgba(255,140,26,.55);
        }
        .btn-submit:active { transform: translateY(0); }

        .divider {
            text-align: center; color: var(--muted);
            font-size: 12px; margin: 24px 0;
            position: relative;
        }
        .divider::before, .divider::after {
            content: ''; position: absolute; top: 50%;
            width: 40%; height: 1px; background: var(--border);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .login-link { text-align: center; font-size: 14px; color: var(--muted); }
        .login-link a { color: var(--orange); text-decoration: none; font-weight: 600; }
        .login-link a:hover { opacity: .8; }

        @media(max-width: 480px) {
            .auth-card { padding: 32px 24px; }
            .two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand">
        <h1>AeroFocus</h1>
        <p>Assignment Tracker for In-School Working Students</p>
    </div>

    <div class="form-title">Create your account </div>
    <div class="form-subtitle">Fill in the details below to get started</div>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="two-col">
            <!-- Username -->
            <div class="form-group">
                <label for="reg_username">Username</label>
                <div class="input-wrap">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <input type="text" id="reg_username" name="username"
                           placeholder="e.g. jdoe2024"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
            </div>

            <!-- Full Name -->
            <div class="form-group">
                <label for="reg_fullname">Full Name</label>
                <div class="input-wrap">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <input type="text" id="reg_fullname" name="full_name"
                           placeholder="e.g. Juan Dela Cruz"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
            </div>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="reg_email">Email Address</label>
            <div class="input-wrap">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <input type="email" id="reg_email" name="email"
                       placeholder="you@school.edu.ph"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
        </div>

        <!-- Occupation -->
        <div class="form-group">
            <label for="reg_occupation">Occupation Type</label>
            <div class="input-wrap">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <select id="reg_occupation" name="occupation_type">
                    <?php
                    $types = ['In-School Working Student','Working Student','Full-time Student','Part-time Student'];
                    foreach ($types as $t):
                        $sel = (($_POST['occupation_type'] ?? 'In-School Working Student') === $t) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $t; ?>" <?php echo $sel; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="two-col">
            <!-- Password -->
            <div class="form-group">
                <label for="reg_password">Password</label>
                <div class="input-wrap">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input type="password" id="reg_password" name="password" placeholder="Min. 6 chars" required>
                    <button type="button" class="show-pass" onclick="togglePass('reg_password', this)" aria-label="Show password" title="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="reg_confirm">Confirm Password</label>
                <div class="input-wrap">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <input type="password" id="reg_confirm" name="confirm" placeholder="Repeat password" required>
                    <button type="button" class="show-pass" onclick="togglePass('reg_confirm', this)" aria-label="Show password" title="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-submit" id="register_submit">Create Account</button>
    </form>

    <div class="divider">or</div>
    <div class="login-link">Already have an account? <a href="login.php">Sign in</a></div>
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
