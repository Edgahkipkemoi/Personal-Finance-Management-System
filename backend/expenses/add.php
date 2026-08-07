<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/expenses.php');
    exit();
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ../../frontend/expenses.php');
    exit();
}

$amount       = $_POST['amount']       ?? '';
$description  = $_POST['description']  ?? '';
$expense_date = $_POST['expense_date'] ?? '';
$category_id  = $_POST['category_id']  ?? '';

if (empty($amount) || empty($description) || empty($expense_date) || empty($category_id)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: ../../frontend/expenses.php');
    exit();
}

if (!is_numeric($amount) || $amount <= 0) {
    $_SESSION['error'] = 'Amount must be a positive number';
    header('Location: ../../frontend/expenses.php');
    exit();
}

try {
    $database = new Database();
    $db       = $database->connect();
    $user_id  = $_SESSION['user_id'];

    // Verify the category belongs to this user or is a system category (user_id IS NULL)
    $cat_check = $db->prepare(
        "SELECT category_id FROM categories WHERE category_id = :cat_id AND (user_id = :user_id OR user_id IS NULL)"
    );
    $cat_check->bindParam(':cat_id',  $category_id);
    $cat_check->bindParam(':user_id', $user_id);
    $cat_check->execute();

    if ($cat_check->rowCount() === 0) {
        $_SESSION['error'] = 'Invalid category selected';
        header('Location: ../../frontend/expenses.php');
        exit();
    }

    $query = "INSERT INTO expenses (amount, description, expense_date, category_id, user_id)
              VALUES (:amount, :description, :expense_date, :category_id, :user_id)";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':amount',       $amount);
    $stmt->bindParam(':description',  $description);
    $stmt->bindParam(':expense_date', $expense_date);
    $stmt->bindParam(':category_id',  $category_id);
    $stmt->bindParam(':user_id',      $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Expense added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add expense. Please try again.';
    }

} catch (Exception $e) {
    error_log('Add expense error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/expenses.php');
exit();
