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
    
    // Get user profile information
    $query = "SELECT user_id, name, email, created_at FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    // Get user statistics
    $stats_query = "SELECT 
                        COUNT(DISTINCT e.expense_id) as total_expenses,
                        COALESCE(SUM(e.amount), 0) as total_spent,
                        COUNT(DISTINCT b.budget_id) as budgets_set,
                        COUNT(DISTINCT c.category_id) as custom_categories
                    FROM users u
                    LEFT JOIN expenses e ON u.user_id = e.user_id
                    LEFT JOIN budgets b ON u.user_id = b.user_id
                    LEFT JOIN categories c ON u.user_id = c.user_id
                    WHERE u.user_id = :user_id";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':user_id', $user_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
    
    // Calculate days since registration
    $registration_date = new DateTime($user['created_at']);
    $current_date = new DateTime();
    $days_registered = $current_date->diff($registration_date)->days;
    
    // Return profile data
    echo json_encode([
        'user_id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'member_since' => date('M d, Y', strtotime($user['created_at'])),
        'days_registered' => $days_registered,
        'stats' => [
            'total_expenses' => $stats['total_expenses'],
            'total_spent' => number_format($stats['total_spent'], 2),
            'budgets_set' => $stats['budgets_set'],
            'custom_categories' => $stats['custom_categories']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>