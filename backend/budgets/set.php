<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/budgets.php');
    exit();
}

$amount = $_POST['amount'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';

// Validate input
if (empty($amount) || empty($month) || empty($year)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: ../../frontend/budgets.php');
    exit();
}

if (!is_numeric($amount) || $amount <= 0) {
    $_SESSION['error'] = 'Budget amount must be a positive number';
    header('Location: ../../frontend/budgets.php');
    exit();
}

if (!is_numeric($month) || $month < 1 || $month > 12) {
    $_SESSION['error'] = 'Invalid month selected';
    header('Location: ../../frontend/budgets.php');
    exit();
}

if (!is_numeric($year) || $year < 2020 || $year > 2030) {
    $_SESSION['error'] = 'Invalid year selected';
    header('Location: ../../frontend/budgets.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Insert or update budget (SQLite doesn't support ON DUPLICATE KEY UPDATE)
    // First, try to update existing budget
    $update_query = "UPDATE budgets SET amount = :amount WHERE user_id = :user_id AND month = :month AND year = :year";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':amount', $amount);
    $update_stmt->bindParam(':user_id', $user_id);
    $update_stmt->bindParam(':month', $month);
    $update_stmt->bindParam(':year', $year);
    $update_stmt->execute();
    
    // If no rows were affected, insert new budget
    if ($update_stmt->rowCount() === 0) {
        $insert_query = "INSERT INTO budgets (amount, month, year, user_id) VALUES (:amount, :month, :year, :user_id)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':amount', $amount);
        $insert_stmt->bindParam(':month', $month);
        $insert_stmt->bindParam(':year', $year);
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->execute();
    }
    
    $_SESSION['success'] = 'Budget set successfully!';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../../frontend/budgets.php');
exit();
?>