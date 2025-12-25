<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Get filter parameters
    $period = $_GET['period'] ?? 'monthly';
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Build date filter
    if ($period === 'weekly') {
        $week = $_GET['week'] ?? date('W');
        $date_filter = "strftime('%W', expense_date) = :week AND strftime('%Y', expense_date) = :year";
        $bind_params = [':week' => $week, ':year' => $year, ':user_id' => $user_id];
    } else {
        $date_filter = "strftime('%m', expense_date) = :month AND strftime('%Y', expense_date) = :year";
        $bind_params = [':month' => sprintf('%02d', $month), ':year' => $year, ':user_id' => $user_id];
    }
    
    // Get summary statistics
    $summary_query = "SELECT 
                        COUNT(*) as transactions,
                        COALESCE(SUM(amount), 0) as total,
                        COALESCE(AVG(amount), 0) as average,
                        COALESCE(MAX(amount), 0) as highest
                      FROM expenses 
                      WHERE user_id = :user_id AND $date_filter";
    
    $summary_stmt = $db->prepare($summary_query);
    foreach ($bind_params as $key => $value) {
        $summary_stmt->bindValue($key, $value);
    }
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch();
    
    // Format summary
    $formatted_summary = [
        'transactions' => $summary['transactions'],
        'total' => number_format($summary['total'], 2),
        'average' => number_format($summary['average'], 2),
        'highest' => number_format($summary['highest'], 2)
    ];
    
    // Get category breakdown
    $category_query = "SELECT c.category_name as name, 
                              COALESCE(SUM(e.amount), 0) as total
                       FROM categories c
                       LEFT JOIN expenses e ON c.category_id = e.category_id 
                       AND e.user_id = :user_id AND $date_filter
                       WHERE (c.user_id = :user_id OR c.user_id IS NULL)
                       GROUP BY c.category_id, c.category_name
                       HAVING total > 0
                       ORDER BY total DESC";
    
    $category_stmt = $db->prepare($category_query);
    foreach ($bind_params as $key => $value) {
        $category_stmt->bindValue($key, $value);
    }
    $category_stmt->execute();
    $categories = $category_stmt->fetchAll();
    
    // Format categories
    $formatted_categories = [];
    foreach ($categories as $category) {
        $formatted_categories[] = [
            'name' => $category['name'],
            'total' => number_format($category['total'], 2)
        ];
    }
    
    // Get daily breakdown
    $daily_query = "SELECT DATE(expense_date) as date, SUM(amount) as amount
                    FROM expenses 
                    WHERE user_id = :user_id AND $date_filter
                    GROUP BY DATE(expense_date)
                    ORDER BY expense_date";
    
    $daily_stmt = $db->prepare($daily_query);
    foreach ($bind_params as $key => $value) {
        $daily_stmt->bindValue($key, $value);
    }
    $daily_stmt->execute();
    $daily_expenses = $daily_stmt->fetchAll();
    
    // Format daily data
    $formatted_daily = [];
    foreach ($daily_expenses as $daily) {
        $formatted_daily[] = [
            'date' => date('M d', strtotime($daily['date'])),
            'amount' => number_format($daily['amount'], 2)
        ];
    }
    
    // Return report data
    echo json_encode([
        'summary' => $formatted_summary,
        'categories' => $formatted_categories,
        'daily' => $formatted_daily
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>