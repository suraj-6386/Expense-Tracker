<?php
// ============================================================
//  index.php — Login Page (Landing Page)
// ============================================================

session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

$error = '';

// -- Handle Login Form Submission ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in both Email and Password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn = getConnection();

        // Use prepared statement to prevent SQL Injection
        $stmt = $conn->prepare(
            'SELECT id, username, email, password, user_role FROM users WHERE email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['email']     = $user['email'];

                $stmt->close();
                $conn->close();

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with this email address.';
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login — Personal Expense Tracker</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="auth-body">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="auth-card">

        <!-- Logo / Header -->
        <div class="auth-header text-center mb-4">
            <div class="auth-logo mb-3">
                <i class="bi bi-wallet2"></i>
            </div>
            <h2 class="fw-bold">Expense Tracker</h2>
            <p class="text-muted">Sign in to manage your finances</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="index.php" novalidate>

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
                        autofocus
                    />
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    />
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg btn-auth">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </div>

        </form>

        <hr/>

        <p class="text-center mb-0 text-muted">
            Don't have an account?
            <a href="register.php" class="fw-semibold text-decoration-none">Create Account</a>
        </p>

        <!-- Demo Credentials Info -->
        <div class="demo-box mt-3">
            <p class="mb-1 fw-semibold"><i class="bi bi-info-circle me-1"></i>Demo Credentials</p>
            <small>
                <strong>Student:</strong> suraj@gmail.com / password<br/>
                <strong>Corporate:</strong> corporate@company.com / password
            </small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
