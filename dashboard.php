<?php
// ============================================================
//  dashboard.php — Main Dashboard
//  Shows: Total Income, Total Expense, Remaining Balance
//         + Recent 10 transactions
// ============================================================

session_start();

// Auth guard — redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$conn    = getConnection();
$user_id = (int) $_SESSION['user_id'];

// ---- Query 1: Total Income for this user -------------------
$incomeRes = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total_income
     FROM transactions
     WHERE user_id = $user_id AND type = 'Income'"
);
$total_income = (float) $incomeRes->fetch_assoc()['total_income'];

// ---- Query 2: Total Expense for this user ------------------
$expenseRes = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total_expense
     FROM transactions
     WHERE user_id = $user_id AND type = 'Expense'"
);
$total_expense = (float) $expenseRes->fetch_assoc()['total_expense'];

// ---- Query 3: Remaining Balance ----------------------------
$balance = $total_income - $total_expense;

// ---- Query 4: Recent 8 Transactions (join with categories) -
$recentRes = $conn->query(
    "SELECT t.id, t.amount, t.type, t.description, t.date,
            c.name AS category_name
     FROM transactions t
     JOIN categories c ON t.category_id = c.id
     WHERE t.user_id = $user_id
     ORDER BY t.date DESC, t.id DESC
     LIMIT 8"
);
$recent_transactions = $recentRes->fetch_all(MYSQLI_ASSOC);

// ---- Query 5: Category breakdown (for income/expense pie summary)
$catBreakdownRes = $conn->query(
    "SELECT c.name AS category_name, t.type, SUM(t.amount) AS total
     FROM transactions t
     JOIN categories c ON t.category_id = c.id
     WHERE t.user_id = $user_id
     GROUP BY c.name, t.type
     ORDER BY t.type, total DESC
     LIMIT 10"
);
$cat_breakdown = $catBreakdownRes->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Format helper
function rupees(float $val): string {
    return '₹ ' . number_format($val, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard — Expense Tracker</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="app-body">

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg app-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <i class="bi bi-wallet2 fs-4"></i>
            <span class="fw-bold">ExpenseTracker</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_transaction.php">
                        <i class="bi bi-plus-circle me-1"></i>Add Transaction
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="history.php">
                        <i class="bi bi-clock-history me-1"></i>History
                    </a>
                </li>
                <li class="nav-item ms-2">
                    <span class="role-badge badge-<?= strtolower($_SESSION['user_role']) ?>">
                        <i class="bi bi-<?= $_SESSION['user_role'] === 'Student' ? 'mortarboard' : 'briefcase' ?> me-1"></i>
                        <?= htmlspecialchars($_SESSION['user_role']) ?>
                    </span>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#"
                       data-bs-toggle="dropdown">
                        <div class="avatar-circle">
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($_SESSION['email']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== MAIN CONTENT ===== -->
<main class="container-fluid px-4 py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['username'])[0]) ?>! 👋</h4>
            <small class="text-muted">Here's your financial overview</small>
        </div>
        <a href="add_transaction.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Transaction
        </a>
    </div>

    <!-- ===== SUMMARY CARDS ===== -->
    <div class="row g-4 mb-4">

        <!-- Total Income -->
        <div class="col-12 col-md-4">
            <div class="summary-card income-card">
                <div class="card-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
                <div class="card-label">Total Income</div>
                <div class="card-amount"><?= rupees($total_income) ?></div>
                <div class="card-sub">All credited transactions</div>
            </div>
        </div>

        <!-- Total Expense -->
        <div class="col-12 col-md-4">
            <div class="summary-card expense-card">
                <div class="card-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
                <div class="card-label">Total Expenses</div>
                <div class="card-amount"><?= rupees($total_expense) ?></div>
                <div class="card-sub">All debited transactions</div>
            </div>
        </div>

        <!-- Remaining Balance -->
        <div class="col-12 col-md-4">
            <div class="summary-card balance-card <?= $balance < 0 ? 'negative' : '' ?>">
                <div class="card-icon"><i class="bi bi-piggy-bank-fill"></i></div>
                <div class="card-label">Remaining Balance</div>
                <div class="card-amount"><?= rupees($balance) ?></div>
                <div class="card-sub"><?= $balance >= 0 ? 'You are in the green! 🎉' : 'Expenses exceed income!' ?></div>
            </div>
        </div>

    </div>

    <!-- ===== EXPENSE RATIO PROGRESS BAR ===== -->
    <?php if ($total_income > 0): ?>
    <div class="info-card mb-4">
        <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold">Expense Ratio</span>
            <span class="text-muted small">
                <?= round(($total_expense / $total_income) * 100, 1) ?>% of income spent
            </span>
        </div>
        <?php $ratio = min(100, ($total_expense / $total_income) * 100); ?>
        <div class="progress" style="height: 10px; border-radius: 999px;">
            <div class="progress-bar <?= $ratio > 80 ? 'bg-danger' : ($ratio > 60 ? 'bg-warning' : 'bg-success') ?>"
                 style="width: <?= $ratio ?>%; border-radius: 999px; transition: width 1s ease;">
            </div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-success">₹0</small>
            <small class="text-danger"><?= rupees($total_income) ?></small>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== RECENT TRANSACTIONS ===== -->
    <div class="info-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Recent Transactions</h5>
            <a href="history.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>

        <?php if (empty($recent_transactions)): ?>
            <div class="empty-state py-5 text-center">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-2 text-muted">No transactions yet. <a href="add_transaction.php">Add your first one!</a></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-head">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $txn): ?>
                        <tr>
                            <td>
                                <span class="text-muted small">
                                    <?= date('d M Y', strtotime($txn['date'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="category-pill"><?= htmlspecialchars($txn['category_name']) ?></span>
                            </td>
                            <td class="text-muted">
                                <?= htmlspecialchars($txn['description'] ?: '—') ?>
                            </td>
                            <td>
                                <?php if ($txn['type'] === 'Income'): ?>
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-arrow-down me-1"></i>Income
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-arrow-up me-1"></i>Expense
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold <?= $txn['type'] === 'Income' ? 'text-success' : 'text-danger' ?>">
                                <?= $txn['type'] === 'Income' ? '+' : '-' ?><?= rupees((float)$txn['amount']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

<!-- Footer -->
<footer class="text-center py-3 mt-4">
    <small class="text-muted">Personal Expense Tracker &copy; <?= date('Y') ?> — DPU MCA Project</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
