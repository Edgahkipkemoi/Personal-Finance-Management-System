<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

try {
    $db      = (new Database())->connect();
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $db->prepare(
        "SELECT e.expense_id AS id, e.expense_date, e.description, e.amount,
                c.category_name
         FROM expenses e
         LEFT JOIN categories c ON e.category_id = c.category_id
         WHERE e.user_id = ?
         ORDER BY e.expense_date DESC, e.created_at DESC"
    );
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    $out = [];
    foreach ($rows as $e) {
        $out[] = [
            'id'          => $e['id'],
            'date'        => date('M d, Y', strtotime($e['expense_date'])),
            'category'    => $e['category_name'] ?? 'Uncategorized',
            'description' => $e['description'],
            'amount'      => number_format($e['amount'], 2),
        ];
    }

    echo json_encode($out);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
