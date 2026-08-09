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
    $month   = (int) ($_GET['month'] ?? date('n'));
    $year    = (int) ($_GET['year']  ?? date('Y'));

    // ── Summary stats ────────────────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT
            COUNT(*)                    AS transactions,
            COALESCE(SUM(amount),  0)   AS total,
            COALESCE(AVG(amount),  0)   AS average,
            COALESCE(MAX(amount),  0)   AS highest
         FROM expenses
         WHERE user_id = ?
           AND MONTH(expense_date) = ?
           AND YEAR(expense_date)  = ?"
    );
    $stmt->execute([$user_id, $month, $year]);
    $summary = $stmt->fetch();

    $formatted_summary = [
        'transactions' => (int) $summary['transactions'],
        'total'        => number_format($summary['total'],   2),
        'average'      => number_format($summary['average'], 2),
        'highest'      => number_format($summary['highest'], 2),
    ];

    // ── Category breakdown ───────────────────────────────────────────────────
    // Positional params avoid HY093 (user_id appears in JOIN and WHERE)
    $stmt = $db->prepare(
        "SELECT c.category_name AS name, COALESCE(SUM(e.amount), 0) AS total
         FROM categories c
         LEFT JOIN expenses e
           ON c.category_id    = e.category_id
          AND e.user_id         = ?
          AND MONTH(e.expense_date) = ?
          AND YEAR(e.expense_date)  = ?
         WHERE c.user_id = ? OR c.user_id IS NULL
         GROUP BY c.category_id, c.category_name
         HAVING total > 0
         ORDER BY total DESC"
    );
    $stmt->execute([$user_id, $month, $year, $user_id]);
    $categories = $stmt->fetchAll();

    $formatted_categories = [];
    foreach ($categories as $cat) {
        $formatted_categories[] = [
            'name'  => $cat['name'],
            'total' => number_format($cat['total'], 2),
        ];
    }

    // ── Daily breakdown ──────────────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT DATE(expense_date) AS date, SUM(amount) AS amount
         FROM expenses
         WHERE user_id = ?
           AND MONTH(expense_date) = ?
           AND YEAR(expense_date)  = ?
         GROUP BY DATE(expense_date)
         ORDER BY DATE(expense_date) ASC"
    );
    $stmt->execute([$user_id, $month, $year]);
    $daily_rows = $stmt->fetchAll();

    $formatted_daily = [];
    foreach ($daily_rows as $row) {
        $formatted_daily[] = [
            'date'   => date('M d', strtotime($row['date'])),
            'amount' => number_format($row['amount'], 2),
        ];
    }

    echo json_encode([
        'summary'    => $formatted_summary,
        'categories' => $formatted_categories,
        'daily'      => $formatted_daily,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
