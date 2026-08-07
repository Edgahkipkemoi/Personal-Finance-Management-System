<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/categories.php');
    exit();
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ../../frontend/categories.php');
    exit();
}

$category_id = $_POST['category_id'] ?? '';

if (empty($category_id) || !is_numeric($category_id)) {
    $_SESSION['error'] = 'Invalid category ID';
    header('Location: ../../frontend/categories.php');
    exit();
}

try {
    $database = new Database();
    $db       = $database->connect();
    $user_id  = $_SESSION['user_id'];

    // Prevent deletion if expenses reference this category
    $check_query = "SELECT COUNT(*) as count FROM expenses WHERE category_id = :category_id AND user_id = :user_id";
    $check_stmt  = $db->prepare($check_query);
    $check_stmt->bindParam(':category_id', $category_id);
    $check_stmt->bindParam(':user_id',     $user_id);
    $check_stmt->execute();
    $expense_count = $check_stmt->fetch()['count'];

    if ($expense_count > 0) {
        $_SESSION['error'] = 'Cannot delete a category that has existing expenses';
        header('Location: ../../frontend/categories.php');
        exit();
    }

    // Only delete user's own categories (not system defaults with user_id IS NULL)
    $query = "DELETE FROM categories WHERE category_id = :category_id AND user_id = :user_id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':user_id',     $user_id);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Category deleted successfully!';
    } else {
        $_SESSION['error'] = 'Category not found or cannot be deleted.';
    }

} catch (Exception $e) {
    error_log('Delete category error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/categories.php');
exit();
