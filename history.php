<?php
// ============================================================
//  history.php — Full Transaction History with Filters
// ============================================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$conn    = getConnection();
$user_id = (int) $_SESSION['user_id'];

// ---- Handle Delete Request ----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int) $_POST['delete_id'];
    $del    = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
    $del->bind_param('ii', $del_id, $user_id);
    $del->execute();
    $del->close();
    header('Location: history.php?deleted=1');
    exit;
}

// ---- Filter Parameters from GET ----------------------------
$filter_type  = $_GET['type']       ?? 'All';
$filter_from  = $_GET['date_from']  ?? '';
$filter_to    = $_GET['date_to']    ?? '';
$filter_cat   = (int)($_GET['category_id'] ?? 0);

// ---- Build Dynamic WHERE clause ----------------------------
$where_parts = ['t.user_id = ?'];
$params      = [$user_id];
$types       = 'i';

if (in_array($filter_type, ['Income', 'Expense'])) {
    $where_parts[] = 't.type = ?';
    $params[]      = $filter_type;
    $types        .= 's';
}

if (!empty($filter_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) {
    $where_parts[] = 't.date >= ?';
    $params[]      = $filter_from;
    $types        .= 's';
}

if (!empty($filter_to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to)) {
    $where_parts[] = 't.date <= ?';
    $params[]      = $filter_to;
    $types        .= 's';
}

if ($filter_cat > 0) {
    $where_parts[] = 't.category_id = ?';
    $params[]      = $filter_cat;
    $types        .= 'i';
}

$where_sql = implode(' AND ', $where_parts);

// ---- Filtered Totals for this result set -------------------
$totStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN t.type='Income'  THEN t.amount ELSE 0 END), 0) AS filtered_income,
        COALESCE(SUM(CASE WHEN t.type='Expense' THEN t.amount ELSE 0 END), 0) AS filtered_expense,
        COUNT(*) AS total_count
     FROM transactions t
     WHERE $where_sql"
);
$totStmt->bind_param($types, ...$params);
$totStmt->execute();
$totals = $totStmt->get_result()->fetch_assoc();
$totStmt->close();

// ---- Fetch Filtered Transactions ---------------------------
$txnStmt = $conn->prepare(
    "SELECT t.id, t.amount, t.type, t.description, t.date, c.name AS category_name
     FROM transactions t
     JOIN categories c ON t.category_id = c.id
     WHERE $where_sql
     ORDER BY t.date DESC, t.id DESC"
);
$txnStmt->bind_param($types, ...$params);
$txnStmt->execute();
$transactions = $txnStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$txnStmt->close();

// ---- Category list for filter dropdown ---------------------
$catRes     = $conn->query('SELECT id, name FROM categories ORDER BY name');
$categories = $catRes->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Transaction History — Expense Tracker</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="app-body">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg app-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <i class="bi bi-wallet2 fs-4"></i><span class="fw-bold">ExpenseTracker</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="add_transaction.php"><i class="bi bi-plus-circle me-1"></i>Add Transaction</a></li>
                <li class="nav-item"><a class="nav-link active" href="history.php"><i class="bi bi-clock-history me-1"></i>History</a></li>
                <li class="nav-item ms-2">
                    <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container-fluid px-4 py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Transaction History</h4>
            <small class="text-muted"><?= number_format((int)$totals['total_count']) ?> records found</small>
        </div>
        <a href="add_transaction.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add New
        </a>
    </div>

    <!-- Deleted Alert -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-warning d-flex align-items-center alert-dismissible fade show">
            <i class="bi bi-trash me-2"></i> Transaction deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== FILTER PANEL ===== -->
    <div class="info-card mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filters</h6>
        <form method="GET" action="history.php" class="row g-3 align-items-end">

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold small">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="All"     <?= $filter_type === 'All'     ? 'selected' : '' ?>>All Types</option>
                    <option value="Income"  <?= $filter_type === 'Income'  ? 'selected' : '' ?>>Income</option>
                    <option value="Expense" <?= $filter_type === 'Expense' ? 'selected' : '' ?>>Expense</option>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold small">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= $filter_cat === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold small">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filter_from) ?>"/>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold small">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filter_to) ?>"/>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="history.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- ===== FILTERED SUMMARY CHIPS ===== -->
    <div class="d-flex gap-3 mb-3 flex-wrap">
        <span class="filter-chip income-chip">
            <i class="bi bi-arrow-down me-1"></i>Income: <strong>₹ <?= number_format((float)$totals['filtered_income'], 2) ?></strong>
        </span>
        <span class="filter-chip expense-chip">
            <i class="bi bi-arrow-up me-1"></i>Expenses: <strong>₹ <?= number_format((float)$totals['filtered_expense'], 2) ?></strong>
        </span>
        <span class="filter-chip balance-chip">
            <i class="bi bi-wallet me-1"></i>Net: <strong>₹ <?= number_format((float)$totals['filtered_income'] - (float)$totals['filtered_expense'], 2) ?></strong>
        </span>
    </div>

    <!-- ===== TRANSACTIONS TABLE ===== -->
    <div class="info-card">
        <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search fs-1 text-muted"></i>
                <p class="mt-2 text-muted">No transactions match your filters.</p>
                <a href="history.php" class="btn btn-outline-primary btn-sm">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-head">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $i => $txn): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($txn['date'])) ?></td>
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
                            <td><span class="category-pill"><?= htmlspecialchars($txn['category_name']) ?></span></td>
                            <td class="text-muted"><?= htmlspecialchars($txn['description'] ?: '—') ?></td>
                            <td class="text-end fw-semibold <?= $txn['type'] === 'Income' ? 'text-success' : 'text-danger' ?>">
                                <?= $txn['type'] === 'Income' ? '+' : '-' ?>₹ <?= number_format((float)$txn['amount'], 2) ?>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="history.php"
                                      onsubmit="return confirm('Delete this transaction?')">
                                    <input type="hidden" name="delete_id" value="<?= $txn['id'] ?>"/>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <!-- Table Footer Totals -->
                    <tfoot class="table-head fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">Filtered Totals</td>
                            <td class="text-end">
                                <span class="text-success">+<?= number_format((float)$totals['filtered_income'], 2) ?></span><br/>
                                <span class="text-danger">-<?= number_format((float)$totals['filtered_expense'], 2) ?></span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

<footer class="text-center py-3 mt-4">
    <small class="text-muted">Personal Expense Tracker &copy; <?= date('Y') ?> — DPU MCA Project</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
