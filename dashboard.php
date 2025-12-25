<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->connect();

// Get current month statistics
$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year = date('Y');

// Total expenses this month
$query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
          WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':month', $current_month);
$stmt->bindParam(':year', $current_year);
$stmt->execute();
$monthly_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Budget for this month
$budget_query = "SELECT amount FROM budgets WHERE user_id = :user_id AND month = :month AND year = :year";
$budget_stmt = $db->prepare($budget_query);
$budget_stmt->bindParam(':user_id', $user_id);
$budget_stmt->bindParam(':month', $current_month);
$budget_stmt->bindParam(':year', $current_year);
$budget_stmt->execute();
$budget = $budget_stmt->fetch(PDO::FETCH_ASSOC);
$budget_amount = $budget ? $budget['amount'] : 0;

// Recent expenses
$recent_query = "SELECT e.*, c.category_name FROM expenses e 
                 LEFT JOIN categories c ON e.category_id = c.category_id 
                 WHERE e.user_id = :user_id ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 5";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->bindParam(':user_id', $user_id);
$recent_stmt->execute();
$recent_expenses = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Category breakdown
$category_query = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) as total 
                   FROM categories c 
                   LEFT JOIN expenses e ON c.category_id = e.category_id AND e.user_id = :user_id 
                   AND MONTH(e.expense_date) = :month AND YEAR(e.expense_date) = :year
                   WHERE c.user_id = :user_id OR c.user_id IS NULL
                   GROUP BY c.category_id, c.category_name 
                   HAVING total > 0 
                   ORDER BY total DESC";
$category_stmt = $db->prepare($category_query);
$category_stmt->bindParam(':user_id', $user_id);
$category_stmt->bindParam(':month', $current_month);
$category_stmt->bindParam(':year', $current_year);
$category_stmt->execute();
$category_breakdown = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Expenses</h5>
                        <h3>KSh <?php echo number_format($monthly_total, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Budget</h5>
                        <h3>KSh <?php echo number_format($budget_amount, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white <?php echo $budget_amount > 0 && $monthly_total > $budget_amount ? 'bg-danger' : 'bg-info'; ?> mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Remaining</h5>
                        <h3>KSh <?php echo number_format($budget_amount - $monthly_total, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Expenses</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>KSh <?php echo number_format($expense['amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="expenses.php" class="btn btn-primary">View All Expenses</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Category Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($category_breakdown as $category): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                                <span>KSh <?php echo number_format($category['total'], 2); ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $monthly_total > 0 ? ($category['total'] / $monthly_total * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
