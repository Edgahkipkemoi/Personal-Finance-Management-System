<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/expenses.html');
    exit();
}

$amount = $_POST['amount'] ?? '';
$description = $_POST['description'] ?? '';
$expense_date = $_POST['expense_date'] ?? '';
$category_id = $_POST['category_id'] ?? '';

// Validate input
if (empty($amount) || empty($description) || empty($expense_date) || empty($category_id)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: ../../frontend/expenses.html');
    exit();
}

if (!is_numeric($amount) || $amount <= 0) {
    $_SESSION['error'] = 'Amount must be a positive number';
    header('Location: ../../frontend/expenses.html');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    $query = "INSERT INTO expenses (amount, description, expense_date, category_id, user_id) 
              VALUES (:amount, :description, :expense_date, :category_id, :user_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':expense_date', $expense_date);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Expense added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add expense';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../../frontend/expenses.html');
exit();
?>