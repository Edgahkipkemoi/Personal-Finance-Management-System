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

$expense_id = $_POST['expense_id'] ?? '';

if (empty($expense_id) || !is_numeric($expense_id)) {
    $_SESSION['error'] = 'Invalid expense ID';
    header('Location: ../../frontend/expenses.php');
    exit();
}

try {
    $database = new Database();
    $db       = $database->connect();
    $user_id  = $_SESSION['user_id'];

    // WHERE clause scopes delete to the owning user — prevents IDOR
    $query = "DELETE FROM expenses WHERE expense_id = :expense_id AND user_id = :user_id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':expense_id', $expense_id);
    $stmt->bindParam(':user_id',    $user_id);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Expense deleted successfully!';
    } else {
        $_SESSION['error'] = 'Expense not found or already deleted.';
    }

} catch (Exception $e) {
    error_log('Delete expense error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/expenses.php');
exit();
