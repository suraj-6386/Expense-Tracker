<?php
// ============================================================
//  register.php — User Registration Page
// ============================================================

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

$error   = '';
$success = '';

// -- Handle Registration Form Submission ---------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $confirm   = trim($_POST['confirm']   ?? '');
    $user_role = trim($_POST['user_role'] ?? '');

    // ---- Validation ----
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

        // Check if email already exists
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists. Please log in.';
            $check->close();
        } else {
            $check->close();

            // Hash password with BCrypt (default_algorithm = PASSWORD_BCRYPT)
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare(
                'INSERT INTO users (username, email, password, user_role) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('ssss', $username, $email, $hashed, $user_role);

            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now <a href="index.php">log in</a>.';
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
    <title>Register — Personal Expense Tracker</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="auth-body">

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <div class="auth-card">

        <!-- Header -->
        <div class="auth-header text-center mb-4">
            <div class="auth-logo mb-3">
                <i class="bi bi-person-plus"></i>
            </div>
            <h2 class="fw-bold">Create Account</h2>
            <p class="text-muted">Join as a Student or Corporate user</p>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $success /* HTML link — safe output */ ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="register.php" novalidate>

            <!-- Full Name -->
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="John Doe"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autofocus
                    />
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    />
                </div>
            </div>

            <!-- Role Selection -->
            <div class="mb-3">
                <label class="form-label fw-semibold">I am a...</label>
                <div class="role-selector d-flex gap-3">
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
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Minimum 6 characters"
                        required
                    />
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="confirm" class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input
                        type="password"
                        class="form-control"
                        id="confirm"
                        name="confirm"
                        placeholder="Repeat your password"
                        required
                    />
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg btn-auth">
                    <i class="bi bi-person-check me-2"></i>Create Account
                </button>
            </div>

        </form>

        <hr/>
        <p class="text-center mb-0 text-muted">
            Already have an account?
            <a href="index.php" class="fw-semibold text-decoration-none">Sign In</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Highlight selected role card
    document.querySelectorAll('.role-card input').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
            this.closest('.role-card').classList.add('selected');
        });
    });
</script>
</body>
</html>
