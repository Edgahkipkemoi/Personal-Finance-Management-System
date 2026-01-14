<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Get all expenses for the user
    $query = "SELECT e.expense_date, c.category_name, e.description, e.amount, e.created_at
              FROM expenses e
              LEFT JOIN categories c ON e.category_id = c.category_id
              WHERE e.user_id = :user_id
              ORDER BY e.expense_date DESC, e.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'expenses_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Date', 'Category', 'Description', 'Amount (KSh)', 'Created At']);
    
    // Add data rows
    $total = 0;
    foreach ($expenses as $expense) {
        fputcsv($output, [
            $expense['expense_date'],
            $expense['category_name'] ?? 'Uncategorized',
            $expense['description'],
            number_format($expense['amount'], 2),
            $expense['created_at']
        ]);
        $total += $expense['amount'];
    }
    
    // Add summary row
    fputcsv($output, []);
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Expenses', '', '', number_format($total, 2)]);
    fputcsv($output, ['Total Transactions', '', '', count($expenses)]);
    fputcsv($output, ['Export Date', '', '', date('Y-m-d H:i:s')]);
    
    fclose($output);
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to export data: ' . $e->getMessage();
    header('Location: ../../frontend/expenses.html');
    exit();
}
?>