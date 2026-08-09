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
    $user_name    = $_SESSION['user_name'] ?? 'User';
    $current_month = (int) date('n');
    $current_year  = (int) date('Y');

    // Monthly total expenses
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM expenses
         WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?"
    );
    $stmt->execute([$user_id, $current_month, $current_year]);
    $monthly_total = (float) $stmt->fetchColumn();

    // Monthly budget
    $stmt = $db->prepare(
        "SELECT amount FROM budgets
         WHERE user_id = ? AND month = ? AND year = ?"
    );
    $stmt->execute([$user_id, $current_month, $current_year]);
    $budget_row    = $stmt->fetch();
    $budget_amount = $budget_row ? (float) $budget_row['amount'] : 0;

    // Recent 5 expenses
    $stmt = $db->prepare(
        "SELECT e.expense_date, e.description, e.amount, c.category_name
         FROM expenses e
         LEFT JOIN categories c ON e.category_id = c.category_id
         WHERE e.user_id = ?
         ORDER BY e.expense_date DESC, e.created_at DESC
         LIMIT 5"
    );
    $stmt->execute([$user_id]);
    $recent_expenses = $stmt->fetchAll();

    $formatted_expenses = [];
    foreach ($recent_expenses as $e) {
        $formatted_expenses[] = [
            'date'        => date('M d, Y', strtotime($e['expense_date'])),
            'category'    => $e['category_name'] ?? 'Uncategorized',
            'description' => $e['description'],
            'amount'      => number_format($e['amount'], 2),
        ];
    }

    // Category breakdown for current month
    // Use positional params — avoids HY093 (repeated named placeholder across JOIN + WHERE)
    $stmt = $db->prepare(
        "SELECT c.category_name, COALESCE(SUM(e.amount), 0) AS total
         FROM categories c
         LEFT JOIN expenses e
           ON c.category_id = e.category_id
          AND e.user_id = ?
          AND MONTH(e.expense_date) = ?
          AND YEAR(e.expense_date)  = ?
         WHERE c.user_id = ? OR c.user_id IS NULL
         GROUP BY c.category_id, c.category_name
         HAVING total > 0
         ORDER BY total DESC"
    );
    $stmt->execute([$user_id, $current_month, $current_year, $user_id]);
    $categories = $stmt->fetchAll();

    $formatted_categories = [];
    foreach ($categories as $cat) {
        $pct = $monthly_total > 0 ? ($cat['total'] / $monthly_total * 100) : 0;
        $formatted_categories[] = [
            'name'       => $cat['category_name'],
            'total'      => number_format($cat['total'], 2),
            'percentage' => round($pct, 1),
        ];
    }

    echo json_encode([
        'user_name'          => $user_name,
        'monthly_total'      => number_format($monthly_total, 2),
        'budget_amount'      => number_format($budget_amount, 2),
        'remaining'          => number_format($budget_amount - $monthly_total, 2),
        'recent_expenses'    => $formatted_expenses,
        'category_breakdown' => $formatted_categories,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
