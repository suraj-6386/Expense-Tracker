<?php
// ============================================================
//  register.php — User Registration Page
// ============================================================

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $confirm   = trim($_POST['confirm']   ?? '');
    $user_role = trim($_POST['user_role'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($confirm) || empty($user_role)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($user_role, ['Student', 'Corporate'])) {
        $error = 'Invalid role selected.';
    } else {
        $conn = getConnection();

        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists. Please log in.';
            $check->close();
        } else {
            $check->close();

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                'INSERT INTO users (username, email, password, user_role) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('ssss', $username, $email, $hashed, $user_role);

            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now <a href="index.php">sign in</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }

            $stmt->close();
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Create Account — Ledger</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="auth-body">

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <div class="auth-card">

        <!-- Wordmark -->
        <div class="text-center mb-4">
            <div class="auth-wordmark mb-1">
                <i class="bi bi-journal-bookmark" style="font-size:1.5rem; color: var(--sage);"></i>
                Ledger<span class="wm-dot"></span>
            </div>
            <p class="auth-subtitle">Start tracking. One entry at a time.</p>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="bi bi-check-circle me-2"></i>
                <?= $success /* safe — HTML link intentional */ ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="register.php" novalidate>

            <!-- Full Name -->
            <div class="underline-field">
                <label for="username">Full Name</label>
                <i class="bi bi-person field-icon"></i>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="e.g. Suraj Gupta"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autofocus
                />
            </div>

            <!-- Email -->
            <div class="underline-field">
                <label for="email">Email Address</label>
                <i class="bi bi-envelope field-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                />
            </div>

            <!-- Role Selection -->
            <div class="mb-4">
                <div style="font-size:.72rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--text-muted); margin-bottom:.75rem; font-family:var(--font-sans);">
                    I am a...
                </div>
                <div class="d-flex gap-3">
                    <label class="role-card flex-fill <?= (($_POST['user_role'] ?? '') === 'Student') ? 'selected' : '' ?>">
                        <input type="radio" name="user_role" value="Student"
                               <?= (($_POST['user_role'] ?? '') === 'Student') ? 'checked' : '' ?> required/>
                        <i class="bi bi-mortarboard d-block mb-1"></i>
                        <span>Student</span>
                    </label>
                    <label class="role-card flex-fill <?= (($_POST['user_role'] ?? '') === 'Corporate') ? 'selected' : '' ?>">
                        <input type="radio" name="user_role" value="Corporate"
                               <?= (($_POST['user_role'] ?? '') === 'Corporate') ? 'checked' : '' ?> required/>
                        <i class="bi bi-briefcase d-block mb-1"></i>
                        <span>Corporate</span>
                    </label>
                </div>
            </div>

            <!-- Password -->
            <div class="underline-field">
                <label for="password">Password</label>
                <i class="bi bi-lock field-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimum 6 characters"
                    required
                />
            </div>

            <!-- Confirm Password -->
            <div class="underline-field">
                <label for="confirm">Confirm Password</label>
                <i class="bi bi-shield-lock field-icon"></i>
                <input
                    type="password"
                    id="confirm"
                    name="confirm"
                    placeholder="Repeat your password"
                    required
                />
            </div>

            <div class="mt-4 mb-3">
                <button type="submit" class="btn-auth">
                    Create Account &rarr;
                </button>
            </div>

        </form>

        <div class="auth-divider">or</div>

        <p class="text-center mb-0" style="font-family:var(--font-sans); font-size:.88rem; color:var(--text-muted);">
            Already have an account?
            <a href="index.php" style="color:var(--text); font-weight:600; text-decoration:none;">Sign in</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.role-card input').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
            this.closest('.role-card').classList.add('selected');
        });
    });
</script>
</body>
</html>
