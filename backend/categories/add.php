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

$category_name = trim($_POST['category_name'] ?? '');

// Validate input
if (empty($category_name)) {
    $_SESSION['error'] = 'Category name is required';
    header('Location: ../../frontend/categories.php');
    exit();
}

if (strlen($category_name) > 50) {
    $_SESSION['error'] = 'Category name must be 50 characters or less';
    header('Location: ../../frontend/categories.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Check if category already exists for this user
    $check_query = "SELECT category_id FROM categories WHERE category_name = :category_name AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':category_name', $category_name);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Category already exists';
        header('Location: ../../frontend/categories.php');
        exit();
    }
    
    // Insert new category
    $query = "INSERT INTO categories (category_name, user_id) VALUES (:category_name, :user_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_name', $category_name);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Category added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add category';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../../frontend/categories.php');
exit();
?>