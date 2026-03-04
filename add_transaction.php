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

        $stmt = $conn->prepare(
            'INSERT INTO transactions (user_id, amount, type, category_id, description, date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('idsiss', $user_id, $amount, $type, $category_id, $description, $date);

        if ($stmt->execute()) {
            $success = 'Transaction recorded successfully!';
            $_POST = [];
        } else {
            $error = 'Failed to save transaction. Please try again.';
        }

        $stmt->close();
    }
}

$conn->close();

// Dynamic heading based on selected type
$selectedType = $_POST['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Record Transaction — Ledger</title>

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
                <li class="nav-item"><a class="nav-link active" href="add_transaction.php"><i class="bi bi-plus-circle me-1"></i>Record</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="bi bi-list-ul me-1"></i>History</a></li>
                <li class="nav-item ms-2">
                    <a class="nav-link" href="logout.php" style="color: rgba(244,243,239,.5)!important;">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- MAIN -->
<main class="container py-5" style="max-width: 620px;">

    <!-- Dynamic page heading -->
    <div class="mb-5">
        <h1 class="page-heading" id="pageHeading">
            <?php if ($selectedType === 'Income'): ?>
                What did you earn today?
            <?php elseif ($selectedType === 'Expense'): ?>
                Record a new expense.
            <?php else: ?>
                What would you like to record?
            <?php endif; ?>
        </h1>
        <p class="page-subheading">Track it carefully — every rupee tells a story.</p>
    </div>

    <!-- Alerts -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success d-flex align-items-center mb-4">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            &nbsp;—&nbsp;<a href="dashboard.php">Back to overview</a> or record another below.
        </div>
    <?php endif; ?>

    <div class="info-card">
        <form method="POST" action="add_transaction.php" novalidate>

            <!-- Transaction Type Toggle -->
            <div class="mb-5">
                <div class="ul-label mb-3">Transaction Type <span style="color:var(--rose);">*</span></div>
                <div class="type-toggle d-flex gap-3">
                    <label class="type-btn flex-fill income-btn <?= ($selectedType === 'Income') ? 'active' : '' ?>">
                        <input type="radio" name="type" value="Income" <?= ($selectedType === 'Income') ? 'checked' : '' ?> required/>
                        <i class="bi bi-arrow-down-circle"></i> Income
                    </label>
                    <label class="type-btn flex-fill expense-btn <?= ($selectedType === 'Expense') ? 'active' : '' ?>">
                        <input type="radio" name="type" value="Expense" <?= ($selectedType === 'Expense') ? 'checked' : '' ?> required/>
                        <i class="bi bi-arrow-up-circle"></i> Expense
                    </label>
                </div>
            </div>

            <!-- Amount — underline style with serif prefix -->
            <div class="ul-group mb-4">
                <label class="ul-label">Amount (₹) <span style="color:var(--rose);">*</span></label>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="ul-amount-prefix">₹</span>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        placeholder="0.00"
                        min="0.01"
                        step="0.01"
                        value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                        required
                        style="font-size:1.5rem; font-family:var(--font-serif); font-weight:700; flex:1;"
                    />
                </div>
            </div>

            <!-- Category — underline select -->
            <div class="ul-group mb-4">
                <label class="ul-label" for="category_id">Category <span style="color:var(--rose);">*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">— Select a category —</option>
                    <?php
                    $prev_type = '';
                    foreach ($categories as $cat):
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

            <!-- Date — underline input -->
            <div class="ul-group mb-4">
                <label class="ul-label" for="date">Date <span style="color:var(--rose);">*</span></label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>"
                    max="<?= date('Y-m-d') ?>"
                    required
                />
            </div>

            <!-- Description — underline textarea -->
            <div class="ul-group mb-5">
                <label class="ul-label" for="description">
                    A note about this <span style="font-weight:400; font-style:italic; text-transform:none; letter-spacing:0; color:var(--text-subtle);">(optional)</span>
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="2"
                    placeholder="e.g. Grocery shopping at D-Mart…"
                    maxlength="255"
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <div style="text-align:right; font-size:.72rem; color:var(--text-subtle); font-family:var(--font-sans); margin-top:.25rem;">
                    <span id="charCount">0</span>/255
                </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    Save to Ledger &rarr;
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Overview
                </a>
            </div>

        </form>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Highlight selected type + update page heading
    const headings = {
        Income:  'What did you earn today?',
        Expense: 'Record a new expense.',
        default: 'What would you like to record?'
    };

    document.querySelectorAll('.type-btn input').forEach(r => {
        r.addEventListener('change', function () {
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            this.closest('.type-btn').classList.add('active');
            document.getElementById('pageHeading').textContent = headings[this.value] || headings.default;
        });
    });

    // Character counter
    const desc = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    function updateCount() { charCount.textContent = desc.value.length; }
    desc.addEventListener('input', updateCount);
    updateCount();
</script>
</body>
</html>
