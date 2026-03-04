<?php
// ============================================================
//  dashboard.php — Main Dashboard
// ============================================================

session_start();

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

// Time-based greeting
$hour = (int) date('H');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 17)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';

$firstName = htmlspecialchars(explode(' ', $_SESSION['username'])[0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard — Ledger</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="app-body">

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg app-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-journal-bookmark" style="color: var(--sage);"></i>
            Ledger<span class="brand-dot"></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-grid-1x2 me-1"></i>Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_transaction.php">
                        <i class="bi bi-plus-circle me-1"></i>Record
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="history.php">
                        <i class="bi bi-list-ul me-1"></i>History
                    </a>
                </li>

                <li class="nav-item ms-2">
                    <span class="role-badge badge-<?= strtolower($_SESSION['user_role']) ?>">
                        <i class="bi bi-<?= $_SESSION['user_role'] === 'Student' ? 'mortarboard' : 'briefcase' ?> me-1"></i>
                        <?= htmlspecialchars($_SESSION['user_role']) ?>
                    </span>
                </li>

                <li class="nav-item dropdown ms-1">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <div class="avatar-circle"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                        <span style="font-size:.85rem;"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($_SESSION['email']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php" style="color:var(--rose-deep);">
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
    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-heading"><?= $greeting ?>, <?= $firstName ?>.</h1>
            <p class="page-subheading">Here's where your money stands today.</p>
        </div>
        <a href="add_transaction.php" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> Record Transaction
        </a>
    </div>

    <!-- ===== GLASSMORPHISM STAT CARDS ===== -->
    <div class="row g-3 mb-4">

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
                <div class="card-label">Net Balance</div>
                <div class="card-amount"><?= rupees($balance) ?></div>
                <div class="card-sub"><?= $balance >= 0 ? 'You\'re in the green ✦' : 'Expenses exceed income' ?></div>
            </div>
        </div>

    </div>

    <!-- ===== EXPENSE RATIO ===== -->
    <?php if ($total_income > 0): ?>
    <div class="info-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-family:var(--font-sans); font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Expense Ratio</span>
            <?php $ratio = min(100, ($total_expense / $total_income) * 100); ?>
            <span style="font-family:var(--font-serif); font-size:1.1rem; font-weight:700; color:var(--text);">
                <?= round($ratio, 1) ?><span style="font-size:.75rem; font-weight:400; color:var(--text-muted); font-family:var(--font-sans);">% spent</span>
            </span>
        </div>
        <div class="ratio-bar-wrap">
            <div class="ratio-bar-fill <?= $ratio > 80 ? 'danger' : ($ratio > 60 ? 'caution' : 'safe') ?>"
                 style="width: <?= $ratio ?>%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <small style="font-family:var(--font-sans); color:var(--sage-deep); font-size:.75rem;">₹ 0</small>
            <small style="font-family:var(--font-sans); color:var(--rose-deep); font-size:.75rem;"><?= rupees($total_income) ?></small>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== ASYMMETRIC LAYOUT: History (wider) + Breakdown (narrower) ===== -->
    <div class="row g-4">

        <!-- Transaction History — takes more visual weight -->
        <div class="col-12 col-lg-8">
            <div class="info-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="section-title"><i class="bi bi-layout-text-window me-2" style="color:var(--text-subtle); font-size:1rem;"></i>Recent Transactions</h5>
                    <a href="history.php" class="btn btn-outline-secondary btn-sm">View all</a>
                </div>

                <?php if (empty($recent_transactions)): ?>
                    <div class="empty-state">
                        <div class="es-icon">📒</div>
                        <div class="es-title">Your financial journey starts here.</div>
                        <p class="es-sub">You haven't recorded anything yet. Every great ledger starts with a single entry.</p>
                        <a href="add_transaction.php" class="es-action">Add First Record</a>
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
                                        <span style="color:var(--text-muted); font-size:.82rem; font-family:var(--font-sans);">
                                            <?= date('d M \'y', strtotime($txn['date'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="category-pill"><?= htmlspecialchars($txn['category_name']) ?></span>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:.88rem;">
                                        <?= htmlspecialchars($txn['description'] ?: '—') ?>
                                    </td>
                                    <td>
                                        <?php if ($txn['type'] === 'Income'): ?>
                                            <span class="filter-chip income-chip" style="padding:.15rem .6rem; font-size:.72rem;">
                                                <i class="bi bi-arrow-down me-1"></i>Income
                                            </span>
                                        <?php else: ?>
                                            <span class="filter-chip expense-chip" style="padding:.15rem .6rem; font-size:.72rem;">
                                                <i class="bi bi-arrow-up me-1"></i>Expense
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold" style="font-family:var(--font-serif); font-size:.95rem; color:<?= $txn['type'] === 'Income' ? 'var(--sage-deep)' : 'var(--rose-deep)' ?>;">
                                        <?= $txn['type'] === 'Income' ? '+' : '−' ?><?= rupees((float)$txn['amount']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Category Breakdown -->
        <div class="col-12 col-lg-4">
            <div class="info-card h-100">
                <h5 class="section-title mb-4"><i class="bi bi-bar-chart me-2" style="color:var(--text-subtle); font-size:1rem;"></i>Top Categories</h5>

                <?php if (empty($cat_breakdown)): ?>
                    <div class="empty-state" style="padding: 2rem 1rem;">
                        <div class="es-icon">📊</div>
                        <p class="es-sub" style="font-size:.82rem;">Category breakdown will appear once you add transactions.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $maxTotal = max(array_column($cat_breakdown, 'total')) ?: 1;
                    foreach ($cat_breakdown as $cat):
                        $pct = ($cat['total'] / $maxTotal) * 100;
                        $isIncome = $cat['type'] === 'Income';
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-family:var(--font-sans); font-size:.82rem; color:var(--text);">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </span>
                            <span style="font-family:var(--font-serif); font-size:.88rem; font-weight:700; color:<?= $isIncome ? 'var(--sage-deep)' : 'var(--rose-deep)' ?>;">
                                ₹<?= number_format((float)$cat['total'], 0) ?>
                            </span>
                        </div>
                        <div class="ratio-bar-wrap" style="height:5px;">
                            <div class="ratio-bar-fill <?= $isIncome ? 'safe' : 'danger' ?>" style="width:<?= round($pct) ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="mt-4 pt-3" style="border-top:1px solid var(--border);">
                        <a href="add_transaction.php" style="font-family:var(--font-sans); font-size:.82rem; color:var(--text-muted); text-decoration:none; display:flex; align-items:center; gap:.4rem;">
                            <i class="bi bi-plus-circle" style="color:var(--sage);"></i>
                            What did you earn or spend today?
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</main>

<!-- Footer -->
<footer class="text-center py-3 mt-5">
    <small>Personal Ledger &copy; <?= date('Y') ?> — DPU MCA Project</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
