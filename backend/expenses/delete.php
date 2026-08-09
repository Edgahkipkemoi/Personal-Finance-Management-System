<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/expenses.html');
    exit();
}

$expense_id = $_POST['expense_id'] ?? '';

if (empty($expense_id)) {
    $_SESSION['error'] = 'Invalid expense ID';
    header('Location: ../../frontend/expenses.html');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    $query = "DELETE FROM expenses WHERE expense_id = :expense_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':expense_id', $expense_id);
    $stmt->bindParam(':user_id', $user_id);
    
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Expense deleted successfully!';
    } else {
        $_SESSION['error'] = 'Expense not found or already deleted';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../../frontend/expenses.html');
exit();
?>