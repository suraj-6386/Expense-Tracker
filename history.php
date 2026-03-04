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
    <title>Transaction History — Ledger</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="app-body">

<!-- NAVBAR -->
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
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2 me-1"></i>Overview</a></li>
                <li class="nav-item"><a class="nav-link" href="add_transaction.php"><i class="bi bi-plus-circle me-1"></i>Record</a></li>
                <li class="nav-item"><a class="nav-link active" href="history.php"><i class="bi bi-list-ul me-1"></i>History</a></li>
                <li class="nav-item ms-2">
                    <a class="nav-link" href="logout.php" style="color:rgba(244,243,239,.5)!important;">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container-fluid px-4 py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-heading">The full ledger.</h1>
            <p class="page-subheading">
                <?= number_format((int)$totals['total_count']) ?> record<?= (int)$totals['total_count'] !== 1 ? 's' : '' ?> found
            </p>
        </div>
        <a href="add_transaction.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Record New
        </a>
    </div>

    <!-- Deleted Alert -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-warning d-flex align-items-center alert-dismissible fade show mb-4">
            <i class="bi bi-trash me-2"></i> Entry removed from your ledger.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== FILTER PANEL ===== -->
    <div class="info-card mb-4">
        <div style="font-family:var(--font-sans); font-size:.72rem; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--text-muted); margin-bottom:1rem;">
            <i class="bi bi-sliders me-1"></i> Filter Records
        </div>
        <form method="GET" action="history.php" class="row g-3 align-items-end">

            <div class="col-12 col-md-3">
                <div class="ul-group" style="margin-bottom:0;">
                    <label class="ul-label">Type</label>
                    <select name="type">
                        <option value="All"     <?= $filter_type === 'All'     ? 'selected' : '' ?>>All Types</option>
                        <option value="Income"  <?= $filter_type === 'Income'  ? 'selected' : '' ?>>Income Only</option>
                        <option value="Expense" <?= $filter_type === 'Expense' ? 'selected' : '' ?>>Expenses Only</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="ul-group" style="margin-bottom:0;">
                    <label class="ul-label">Category</label>
                    <select name="category_id">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filter_cat === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="ul-group" style="margin-bottom:0;">
                    <label class="ul-label">From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filter_from) ?>"/>
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="ul-group" style="margin-bottom:0;">
                    <label class="ul-label">To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filter_to) ?>"/>
                </div>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i>Apply
                </button>
                <a href="history.php" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- ===== SUMMARY CHIPS ===== -->
    <div class="d-flex gap-3 mb-4 flex-wrap">
        <span class="filter-chip income-chip">
            <i class="bi bi-arrow-down me-1"></i>
            Income &nbsp;<strong>₹ <?= number_format((float)$totals['filtered_income'], 2) ?></strong>
        </span>
        <span class="filter-chip expense-chip">
            <i class="bi bi-arrow-up me-1"></i>
            Expenses &nbsp;<strong>₹ <?= number_format((float)$totals['filtered_expense'], 2) ?></strong>
        </span>
        <span class="filter-chip balance-chip">
            <i class="bi bi-wallet me-1"></i>
            Net &nbsp;<strong>₹ <?= number_format((float)$totals['filtered_income'] - (float)$totals['filtered_expense'], 2) ?></strong>
        </span>
    </div>

    <!-- ===== TRANSACTIONS TABLE ===== -->
    <div class="info-card">
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <div class="es-icon">🔍</div>
                <div class="es-title">Nothing to show here.</div>
                <p class="es-sub">
                    <?php if ($filter_type !== 'All' || $filter_from || $filter_to || $filter_cat > 0): ?>
                        No records match your current filters. Try widening the search.
                    <?php else: ?>
                        Your ledger is clean. Start by recording your first transaction.
                    <?php endif; ?>
                </p>
                <?php if ($filter_type !== 'All' || $filter_from || $filter_to || $filter_cat > 0): ?>
                    <a href="history.php" class="es-action">Clear Filters</a>
                <?php else: ?>
                    <a href="add_transaction.php" class="es-action">Record First Entry</a>
                <?php endif; ?>
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
                            <td style="color:var(--text-subtle); font-size:.8rem;"><?= $i + 1 ?></td>
                            <td style="color:var(--text-muted); font-size:.82rem; white-space:nowrap; font-family:var(--font-sans);">
                                <?= date('d M \'y', strtotime($txn['date'])) ?>
                            </td>
                            <td>
                                <?php if ($txn['type'] === 'Income'): ?>
                                    <span class="filter-chip income-chip" style="padding:.15rem .65rem; font-size:.72rem;">
                                        <i class="bi bi-arrow-down me-1"></i>Income
                                    </span>
                                <?php else: ?>
                                    <span class="filter-chip expense-chip" style="padding:.15rem .65rem; font-size:.72rem;">
                                        <i class="bi bi-arrow-up me-1"></i>Expense
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><span class="category-pill"><?= htmlspecialchars($txn['category_name']) ?></span></td>
                            <td style="color:var(--text-muted); font-size:.87rem; max-width:200px;">
                                <?= htmlspecialchars($txn['description'] ?: '—') ?>
                            </td>
                            <td class="text-end" style="font-family:var(--font-serif); font-weight:700; font-size:.95rem; color:<?= $txn['type'] === 'Income' ? 'var(--sage-deep)' : 'var(--rose-deep)' ?>; white-space:nowrap;">
                                <?= $txn['type'] === 'Income' ? '+' : '−' ?>₹ <?= number_format((float)$txn['amount'], 2) ?>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="history.php"
                                      onsubmit="return confirm('Remove this entry from your ledger?')">
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
                    <tfoot>
                        <tr style="border-top:1.5px solid var(--border);">
                            <td colspan="5" class="text-end" style="font-family:var(--font-sans); font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); padding-top:1rem;">
                                Filtered Totals
                            </td>
                            <td class="text-end" style="padding-top:1rem;">
                                <span style="font-family:var(--font-serif); font-size:.9rem; color:var(--sage-deep); font-weight:700;">
                                    +<?= number_format((float)$totals['filtered_income'], 2) ?>
                                </span><br/>
                                <span style="font-family:var(--font-serif); font-size:.9rem; color:var(--rose-deep); font-weight:700;">
                                    −<?= number_format((float)$totals['filtered_expense'], 2) ?>
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

<footer class="text-center py-3 mt-5">
    <small>Personal Ledger &copy; <?= date('Y') ?> — DPU MCA Project</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
