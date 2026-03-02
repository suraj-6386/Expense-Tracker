<?php
// ============================================================
//  add_transaction.php — Add Income or Expense
// ============================================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$conn    = getConnection();
$user_id = (int) $_SESSION['user_id'];

$error   = '';
$success = '';

// ---- Fetch all categories for dropdown ----------------------
$catRes     = $conn->query('SELECT id, name, type FROM categories ORDER BY type, name');
$categories = $catRes->fetch_all(MYSQLI_ASSOC);

// ---- Handle Form Submission ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $type        = trim($_POST['type']        ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $amount      = trim($_POST['amount']      ?? '');
    $description = trim($_POST['description'] ?? '');
    $date        = trim($_POST['date']        ?? '');

    // Validation
    if (empty($type) || empty($category_id) || empty($amount) || empty($date)) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($type, ['Income', 'Expense'])) {
        $error = 'Invalid transaction type selected.';
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $error = 'Amount must be a positive number.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = 'Please select a valid date.';
    } else {
        $amount = (float) $amount;

        // Prepared statement: user_id(i), amount(d), type(s), category_id(i), description(s), date(s)
        $stmt = $conn->prepare(
            'INSERT INTO transactions (user_id, amount, type, category_id, description, date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('idsiss', $user_id, $amount, $type, $category_id, $description, $date);

        if ($stmt->execute()) {
            $success = 'Transaction added successfully!';
            // Clear POST data on success to prevent double-submit
            $_POST = [];
        } else {
            $error = 'Failed to save transaction. Please try again.';
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Add Transaction — Expense Tracker</title>

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
                <li class="nav-item"><a class="nav-link active" href="add_transaction.php"><i class="bi bi-plus-circle me-1"></i>Add Transaction</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="bi bi-clock-history me-1"></i>History</a></li>
                <li class="nav-item ms-2">
                    <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- MAIN -->
<main class="container py-5" style="max-width: 640px;">

    <div class="mb-4 text-center">
        <h4 class="fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Transaction</h4>
        <p class="text-muted small">Record your income or an expense</p>
    </div>

    <!-- Alerts -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($success) ?>
            — <a href="dashboard.php" class="ms-1">Go to Dashboard</a> or add another below.
        </div>
    <?php endif; ?>

    <div class="info-card">
        <form method="POST" action="add_transaction.php" novalidate>

            <!-- Transaction Type Toggle -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Transaction Type <span class="text-danger">*</span></label>
                <div class="type-toggle d-flex gap-3">
                    <label class="type-btn flex-fill income-btn <?= (($_POST['type'] ?? '') === 'Income') ? 'active' : '' ?>">
                        <input type="radio" name="type" value="Income"
                               <?= (($_POST['type'] ?? '') === 'Income') ? 'checked' : '' ?> required/>
                        <i class="bi bi-arrow-down-circle me-1"></i> Income
                    </label>
                    <label class="type-btn flex-fill expense-btn <?= (($_POST['type'] ?? '') === 'Expense') ? 'active' : '' ?>">
                        <input type="radio" name="type" value="Expense"
                               <?= (($_POST['type'] ?? '') === 'Expense') ? 'checked' : '' ?> required/>
                        <i class="bi bi-arrow-up-circle me-1"></i> Expense
                    </label>
                </div>
            </div>

            <!-- Amount -->
            <div class="mb-3">
                <label for="amount" class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text fw-semibold">₹</span>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        class="form-control form-control-lg"
                        placeholder="0.00"
                        min="0.01"
                        step="0.01"
                        value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                        required
                    />
                </div>
            </div>

            <!-- Category -->
            <div class="mb-3">
                <label for="category_id" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <option value="">— Select a category —</option>
                    <?php
                    $prev_type = '';
                    foreach ($categories as $cat):
                        // Group by type with optgroup
                        if ($cat['type'] !== $prev_type && $cat['type'] !== 'Both'):
                            if ($prev_type !== '') echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($cat['type']) . '">';
                            $prev_type = $cat['type'];
                        endif;
                    ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= (($_POST['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="General"><option value="16">Other</option></optgroup>
                </select>
            </div>

            <!-- Date -->
            <div class="mb-3">
                <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    class="form-control"
                    value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>"
                    max="<?= date('Y-m-d') ?>"
                    required
                />
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="form-label fw-semibold">Description <small class="text-muted">(Optional)</small></label>
                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    rows="2"
                    placeholder="e.g. Grocery shopping at D-Mart"
                    maxlength="255"
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <div class="form-text text-end"><span id="charCount">0</span>/255</div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save me-2"></i>Save Transaction
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Highlight selected type
    document.querySelectorAll('.type-btn input').forEach(r => {
        r.addEventListener('change', function () {
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            this.closest('.type-btn').classList.add('active');
        });
    });

    // Character counter for description
    const desc = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    function updateCount() { charCount.textContent = desc.value.length; }
    desc.addEventListener('input', updateCount);
    updateCount();
</script>
</body>
</html>
