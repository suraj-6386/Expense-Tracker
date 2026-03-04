<?php
// ============================================================
//  index.php — Login Page (Landing Page)
// ============================================================

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in both Email and Password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn = getConnection();

        $stmt = $conn->prepare(
            'SELECT id, username, email, password, user_role FROM users WHERE email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
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
    <title>Sign In — Ledger</title>

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
            <p class="auth-subtitle">Your finances, beautifully organized.</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="index.php" novalidate>

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
                    autofocus
                />
            </div>

            <div class="underline-field">
                <label for="password">Password</label>
                <i class="bi bi-lock field-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                />
            </div>

            <div class="mt-4 mb-3">
                <button type="submit" class="btn-auth">
                    Sign In &rarr;
                </button>
            </div>

        </form>

        <div class="auth-divider">or</div>

        <p class="text-center mb-3" style="font-family: var(--font-sans); font-size: .88rem; color: var(--text-muted);">
            New here?
            <a href="register.php" style="color: var(--text); font-weight: 600; text-decoration: none;">Create an account</a>
        </p>

        <!-- Demo Credentials -->
        <div class="demo-box mt-3">
            <p class="mb-1" style="font-weight:600; font-size:.8rem; color:var(--text);">
                <i class="bi bi-info-circle me-1" style="color:var(--sage);"></i>Demo Credentials
            </p>
            <small style="line-height:1.8;">
                <strong>Student:</strong> suraj@gmail.com / password<br/>
                <strong>Corporate:</strong> corporate@company.com / password
            </small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
