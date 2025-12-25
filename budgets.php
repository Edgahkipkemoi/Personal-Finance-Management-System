<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->connect();
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    $query = "INSERT INTO budgets (amount, month, year, user_id) VALUES (:amount, :month, :year, :user_id)
              ON DUPLICATE KEY UPDATE amount = :amount";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Budget saved successfully!';
    } else {
        $_SESSION['error'] = 'Failed to save budget';
    }
    header('Location: budgets.php');
    exit();
}

// Get budgets with spending comparison
$budgets_query = "SELECT b.*, 
                  COALESCE(SUM(e.amount), 0) as spent,
                  (b.amount - COALESCE(SUM(e.amount), 0)) as remaining
                  FROM budgets b
                  LEFT JOIN expenses e ON e.user_id = b.user_id 
                  AND MONTH(e.expense_date) = b.month 
                  AND YEAR(e.expense_date) = b.year
                  WHERE b.user_id = :user_id
                  GROUP BY b.budget_id, b.amount, b.month, b.year
                  ORDER BY b.year DESC, b.month DESC";
$budgets_stmt = $db->prepare($budgets_query);
$budgets_stmt->bindParam(':user_id', $user_id);
$budgets_stmt->execute();
$budgets = $budgets_stmt->fetchAll(PDO::FETCH_ASSOC);

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgets - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Set Monthly Budget</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Budget Amount (KSh)</label>
                                <input type="number" name="amount" step="0.01" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-control" required>
                                    <?php foreach ($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo $num == date('n') ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Year</label>
                                <select name="year" class="form-control" required>
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Set Budget</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Budget Overview</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($budgets)): ?>
                            <p class="text-muted">No budgets set yet. Create your first budget!</p>
                        <?php else: ?>
                            <?php foreach ($budgets as $budget): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h6><?php echo $months[$budget['month']] . ' ' . $budget['year']; ?></h6>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Budget: KSh <?php echo number_format($budget['amount'], 2); ?></span>
                                                <span>Spent: KSh <?php echo number_format($budget['spent'], 2); ?></span>
                                            </div>
                                            <div class="progress mb-2">
                                                <?php 
                                                $percentage = $budget['amount'] > 0 ? ($budget['spent'] / $budget['amount'] * 100) : 0;
                                                $bar_class = $percentage > 100 ? 'bg-danger' : ($percentage > 80 ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <div class="progress-bar <?php echo $bar_class; ?>" 
                                                     style="width: <?php echo min($percentage, 100); ?>%">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                Remaining: KSh <?php echo number_format($budget['remaining'], 2); ?>
                                                <?php if ($budget['remaining'] < 0): ?>
                                                    <span class="text-danger">(Over budget by KSh <?php echo number_format(abs($budget['remaining']), 2); ?>)</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>