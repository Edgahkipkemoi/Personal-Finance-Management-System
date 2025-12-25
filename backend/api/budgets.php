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
    
    // Get budgets with spending comparison
    $budgets_query = "SELECT b.budget_id, b.amount, b.month, b.year,
                             COALESCE(SUM(e.amount), 0) as spent,
                             (b.amount - COALESCE(SUM(e.amount), 0)) as remaining
                      FROM budgets b
                      LEFT JOIN expenses e ON e.user_id = b.user_id 
                      AND strftime('%m', e.expense_date) = printf('%02d', b.month)
                      AND strftime('%Y', e.expense_date) = CAST(b.year AS TEXT)
                      WHERE b.user_id = :user_id
                      GROUP BY b.budget_id, b.amount, b.month, b.year
                      ORDER BY b.year DESC, b.month DESC";
    
    $budgets_stmt = $db->prepare($budgets_query);
    $budgets_stmt->bindParam(':user_id', $user_id);
    $budgets_stmt->execute();
    $budgets = $budgets_stmt->fetchAll();
    
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    // Format budgets
    $formatted_budgets = [];
    foreach ($budgets as $budget) {
        $percentage = $budget['amount'] > 0 ? ($budget['spent'] / $budget['amount'] * 100) : 0;
        $status = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success');
        
        $formatted_budgets[] = [
            'id' => $budget['budget_id'],
            'month_name' => $months[$budget['month']] . ' ' . $budget['year'],
            'amount' => number_format($budget['amount'], 2),
            'spent' => number_format($budget['spent'], 2),
            'remaining' => number_format($budget['remaining'], 2),
            'percentage' => round($percentage, 1),
            'status' => $status
        ];
    }
    
    echo json_encode($formatted_budgets);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>