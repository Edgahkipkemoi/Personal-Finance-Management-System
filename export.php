<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
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
foreach ($expenses as $expense) {
    fputcsv($output, [
        $expense['expense_date'],
        $expense['category_name'],
        $expense['description'],
        number_format($expense['amount'], 2),
        $expense['created_at']
    ]);
}

// Add summary row
fputcsv($output, []);
fputcsv($output, ['SUMMARY']);
fputcsv($output, ['Total Expenses', '', '', number_format(array_sum(array_column($expenses, 'amount')), 2)]);
fputcsv($output, ['Total Transactions', '', '', count($expenses)]);
fputcsv($output, ['Export Date', '', '', date('Y-m-d H:i:s')]);

fclose($output);
exit();
?>