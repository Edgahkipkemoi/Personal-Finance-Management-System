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
    $user_name = $_SESSION['user_name'] ?? 'User';
    
    $current_month = date('m');
    $current_year = date('Y');
    
    // Get monthly total expenses
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
              WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':month', $current_month);
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $monthly_total = $stmt->fetch()['total'];
    
    // Get monthly budget
    $budget_query = "SELECT amount FROM budgets WHERE user_id = :user_id AND month = :month AND year = :year";
    $budget_stmt = $db->prepare($budget_query);
    $budget_stmt->bindParam(':user_id', $user_id);
    $budget_stmt->bindParam(':month', $current_month);
    $budget_stmt->bindParam(':year', $current_year);
    $budget_stmt->execute();
    $budget = $budget_stmt->fetch();
    $budget_amount = $budget ? $budget['amount'] : 0;
    
    // Get recent expenses
    $recent_query = "SELECT e.expense_date, e.description, e.amount, c.category_name 
                     FROM expenses e 
                     LEFT JOIN categories c ON e.category_id = c.category_id 
                     WHERE e.user_id = :user_id 
                     ORDER BY e.expense_date DESC, e.created_at DESC 
                     LIMIT 5";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->bindParam(':user_id', $user_id);
    $recent_stmt->execute();
    $recent_expenses = $recent_stmt->fetchAll();
    
    // Format recent expenses
    $formatted_expenses = [];
    foreach ($recent_expenses as $expense) {
        $formatted_expenses[] = [
            'date' => date('M d, Y', strtotime($expense['expense_date'])),
            'category' => $expense['category_name'] ?? 'Uncategorized',
            'description' => $expense['description'],
            'amount' => number_format($expense['amount'], 2)
        ];
    }
    
    // Get category breakdown
    $category_query = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) as total 
                       FROM categories c 
                       LEFT JOIN expenses e ON c.category_id = e.category_id 
                       AND e.user_id = :user_id 
                       AND MONTH(e.expense_date) = :month 
                       AND YEAR(e.expense_date) = :year
                       WHERE c.user_id = :user_id OR c.user_id IS NULL
                       GROUP BY c.category_id, c.category_name 
                       HAVING total > 0 
                       ORDER BY total DESC";
    $category_stmt = $db->prepare($category_query);
    $category_stmt->bindParam(':user_id', $user_id);
    $category_stmt->bindParam(':month', $current_month);
    $category_stmt->bindParam(':year', $current_year);
    $category_stmt->execute();
    $categories = $category_stmt->fetchAll();
    
    // Format category breakdown
    $formatted_categories = [];
    foreach ($categories as $category) {
        $percentage = $monthly_total > 0 ? ($category['total'] / $monthly_total * 100) : 0;
        $formatted_categories[] = [
            'name' => $category['category_name'],
            'total' => number_format($category['total'], 2),
            'percentage' => round($percentage, 1)
        ];
    }
    
    // Return dashboard data
    echo json_encode([
        'user_name' => $user_name,
        'monthly_total' => number_format($monthly_total, 2),
        'budget_amount' => number_format($budget_amount, 2),
        'remaining' => number_format($budget_amount - $monthly_total, 2),
        'recent_expenses' => $formatted_expenses,
        'category_breakdown' => $formatted_categories
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>