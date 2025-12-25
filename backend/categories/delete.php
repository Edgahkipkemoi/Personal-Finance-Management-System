<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/categories.php');
    exit();
}

$category_id = $_POST['category_id'] ?? '';

if (empty($category_id)) {
    $_SESSION['error'] = 'Invalid category ID';
    header('Location: ../../frontend/categories.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Check if category has expenses
    $check_query = "SELECT COUNT(*) as count FROM expenses WHERE category_id = :category_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':category_id', $category_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    $expense_count = $check_stmt->fetch()['count'];
    
    if ($expense_count > 0) {
        $_SESSION['error'] = 'Cannot delete category with existing expenses';
        header('Location: ../../frontend/categories.php');
        exit();
    }
    
    // Delete category (only user's own categories)
    $query = "DELETE FROM categories WHERE category_id = :category_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Category deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete category or category not found';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../../frontend/categories.php');
exit();
?>