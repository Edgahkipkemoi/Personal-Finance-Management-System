<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/budgets.php');
    exit();
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ../../frontend/budgets.php');
    exit();
}

$amount = $_POST['amount'] ?? '';
$month  = $_POST['month']  ?? '';
$year   = $_POST['year']   ?? '';

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
    $db       = $database->connect();
    $user_id  = $_SESSION['user_id'];

    // Use INSERT … ON DUPLICATE KEY UPDATE (MySQL) so one round-trip handles both cases
    $query = "INSERT INTO budgets (amount, month, year, user_id)
              VALUES (:amount, :month, :year, :user_id)
              ON DUPLICATE KEY UPDATE amount = :amount";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':amount',  $amount);
    $stmt->bindParam(':month',   $month);
    $stmt->bindParam(':year',    $year);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $_SESSION['success'] = 'Budget set successfully!';

} catch (Exception $e) {
    error_log('Set budget error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/budgets.php');
exit();
