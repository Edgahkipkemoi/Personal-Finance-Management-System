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
    
    // Get all expenses for the user
    $query = "SELECT e.expense_id as id, e.expense_date, e.description, e.amount, c.category_name 
              FROM expenses e 
              LEFT JOIN categories c ON e.category_id = c.category_id 
              WHERE e.user_id = :user_id 
              ORDER BY e.expense_date DESC, e.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $expenses = $stmt->fetchAll();
    
    // Format expenses
    $formatted_expenses = [];
    foreach ($expenses as $expense) {
        $formatted_expenses[] = [
            'id' => $expense['id'],
            'date' => date('M d, Y', strtotime($expense['expense_date'])),
            'category' => $expense['category_name'] ?? 'Uncategorized',
            'description' => $expense['description'],
            'amount' => number_format($expense['amount'], 2)
        ];
    }
    
    echo json_encode($formatted_expenses);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>