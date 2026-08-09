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
        "SELECT b.budget_id, b.amount, b.month, b.year,
                COALESCE(SUM(e.amount), 0)                        AS spent,
                (b.amount - COALESCE(SUM(e.amount), 0))           AS remaining
         FROM budgets b
         LEFT JOIN expenses e
           ON e.user_id           = b.user_id
          AND MONTH(e.expense_date) = b.month
          AND YEAR(e.expense_date)  = b.year
         WHERE b.user_id = ?
         GROUP BY b.budget_id, b.amount, b.month, b.year
         ORDER BY b.year DESC, b.month DESC"
    );
    $stmt->execute([$user_id]);
    $budgets = $stmt->fetchAll();

    $months = [
        1=>'January', 2=>'February', 3=>'March',    4=>'April',
        5=>'May',     6=>'June',     7=>'July',      8=>'August',
        9=>'September',10=>'October',11=>'November',12=>'December',
    ];

    $out = [];
    foreach ($budgets as $b) {
        $pct    = $b['amount'] > 0 ? ($b['spent'] / $b['amount'] * 100) : 0;
        $status = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
        $out[]  = [
            'id'         => $b['budget_id'],
            'month_name' => $months[(int)$b['month']] . ' ' . $b['year'],
            'amount'     => number_format($b['amount'],    2),
            'spent'      => number_format($b['spent'],     2),
            'remaining'  => number_format($b['remaining'], 2),
            'percentage' => round($pct, 1),
            'status'     => $status,
        ];
    }

    echo json_encode($out);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
