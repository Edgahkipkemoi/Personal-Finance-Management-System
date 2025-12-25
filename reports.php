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

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Build date filter based on period
if ($period == 'weekly') {
    $week = isset($_GET['week']) ? $_GET['week'] : date('W');
    $date_filter = "WEEK(expense_date, 1) = :week AND YEAR(expense_date) = :year";
} else {
    $date_filter = "MONTH(expense_date) = :month AND YEAR(expense_date) = :year";
}

// Monthly/Weekly summary
$summary_query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    MAX(amount) as max_amount,
                    MIN(amount) as min_amount
                  FROM expenses 
                  WHERE user_id = :user_id AND $date_filter";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->bindParam(':user_id', $user_id);
if ($period == 'weekly') {
    $summary_stmt->bindParam(':week', $week);
} else {
    $summary_stmt->bindParam(':month', $month);
}
$summary_stmt->bindParam(':year', $year);
$summary_stmt->execute();
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

// Category breakdown
$category_query = "SELECT c.category_name, 
                          COUNT(e.expense_id) as transaction_count,
                          SUM(e.amount) as total_amount,
                          AVG(e.amount) as avg_amount
                   FROM categories c
                   LEFT JOIN expenses e ON c.category_id = e.category_id 
                   AND e.user_id = :user_id AND $date_filter
                   WHERE (c.user_id = :user_id OR c.user_id IS NULL)
                   GROUP BY c.category_id, c.category_name
                   HAVING total_amount > 0
                   ORDER BY total_amount DESC";
$category_stmt = $db->prepare($category_query);
$category_stmt->bindParam(':user_id', $user_id);
if ($period == 'weekly') {
    $category_stmt->bindParam(':week', $week);
} else {
    $category_stmt->bindParam(':month', $month);
}
$category_stmt->bindParam(':year', $year);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Daily breakdown for the period
if ($period == 'weekly') {
    $daily_query = "SELECT DATE(expense_date) as expense_date, SUM(amount) as daily_total
                    FROM expenses 
                    WHERE user_id = :user_id AND WEEK(expense_date, 1) = :week AND YEAR(expense_date) = :year
                    GROUP BY DATE(expense_date)
                    ORDER BY expense_date";
} else {
    $daily_query = "SELECT DATE(expense_date) as expense_date, SUM(amount) as daily_total
                    FROM expenses 
                    WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year
                    GROUP BY DATE(expense_date)
                    ORDER BY expense_date";
}
$daily_stmt = $db->prepare($daily_query);
$daily_stmt->bindParam(':user_id', $user_id);
if ($period == 'weekly') {
    $daily_stmt->bindParam(':week', $week);
} else {
    $daily_stmt->bindParam(':month', $month);
}
$daily_stmt->bindParam(':year', $year);
$daily_stmt->execute();
$daily_expenses = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Reports - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2>Financial Reports</h2>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Period</label>
                        <select name="period" class="form-control" onchange="this.form.submit()">
                            <option value="monthly" <?php echo $period == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="weekly" <?php echo $period == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        </select>
                    </div>
                    <?php if ($period == 'monthly'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $num == $month ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="col-md-3">
                        <label class="form-label">Week</label>
                        <select name="week" class="form-control" onchange="this.form.submit()">
                            <?php for ($w = 1; $w <= 53; $w++): ?>
                                <option value="<?php echo $w; ?>" <?php echo $w == (isset($week) ? $week : date('W')) ? 'selected' : ''; ?>>
                                    Week <?php echo $w; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control" onchange="this.form.submit()">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Total Spent</h5>
                        <h3>KSh <?php echo number_format($summary['total_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5>Transactions</h5>
                        <h3><?php echo $summary['total_transactions'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5>Average</h5>
                        <h3>KSh <?php echo number_format($summary['avg_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5>Highest</h5>
                        <h3>KSh <?php echo number_format($summary['max_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Category Breakdown Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Spending by Category</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Daily Spending Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Daily Spending Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Details Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Category Breakdown</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Transactions</th>
                            <th>Total Amount</th>
                            <th>Average</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td><?php echo $category['transaction_count']; ?></td>
                            <td>KSh <?php echo number_format($category['total_amount'], 2); ?></td>
                            <td>KSh <?php echo number_format($category['avg_amount'], 2); ?></td>
                            <td><?php echo $summary['total_amount'] > 0 ? number_format(($category['total_amount'] / $summary['total_amount']) * 100, 1) : 0; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Category Pie Chart
        const categoryData = {
            labels: <?php echo json_encode(array_column($categories, 'category_name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($categories, 'total_amount')); ?>,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            }]
        };

        new Chart(document.getElementById('categoryChart'), {
            type: 'pie',
            data: categoryData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Daily Line Chart
        const dailyData = {
            labels: <?php echo json_encode(array_column($daily_expenses, 'expense_date')); ?>,
            datasets: [{
                label: 'Daily Spending',
                data: <?php echo json_encode(array_column($daily_expenses, 'daily_total')); ?>,
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1
            }]
        };

        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: dailyData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>