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

$current_year = (int)date('Y');
if (!is_numeric($year) || $year < 2020 || $year > ($current_year + 5)) {
    $_SESSION['error'] = 'Invalid year selected';
    header('Location: ../../frontend/budgets.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Check if budget exists for this month/year
    $check_query = "SELECT amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$user_id, $month, $year]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // ADD to existing budget
        $update_query = "UPDATE budgets SET amount = amount + ? WHERE user_id = ? AND month = ? AND year = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$amount, $user_id, $month, $year]);
        
        $old_amount = number_format($existing['amount'], 2);
        $new_total = number_format($existing['amount'] + $amount, 2);
        $_SESSION['success'] = "Budget updated! Added KSh " . number_format($amount, 2) . " to existing KSh $old_amount. New total: KSh $new_total";
    } else {
        // Insert new budget
        $insert_query = "INSERT INTO budgets (amount, month, year, user_id) VALUES (?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$amount, $month, $year, $user_id]);
        
        $_SESSION['success'] = 'Budget set successfully for KSh ' . number_format($amount, 2) . '!';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../../frontend/budgets.php');
exit();
?>