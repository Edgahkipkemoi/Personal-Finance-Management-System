<?php
/**
 * GET /backend/api/mpesa.php
 * Returns M-Pesa transaction history and payment history for the logged-in user.
 *
 * ?action=transactions  — imported M-Pesa SMS transactions
 * ?action=payments      — STK push payment records
 * ?action=summary       — aggregate stats for dashboard widget
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$action  = $_GET['action'] ?? 'summary';

try {
    $db = (new Database())->connect();

    switch ($action) {

        case 'transactions':
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $stmt  = $db->prepare(
                'SELECT transaction_id, mpesa_code, transaction_type, amount,
                        counterparty, transaction_date, balance_after, expense_id
                 FROM mpesa_transactions
                 WHERE user_id = ?
                 ORDER BY transaction_date DESC
                 LIMIT ?'
            );
            $stmt->execute([$user_id, $limit]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$r) {
                $r['amount']           = number_format((float)$r['amount'], 2);
                $r['balance_after']    = $r['balance_after']
                                         ? number_format((float)$r['balance_after'], 2)
                                         : null;
                $r['transaction_date'] = date('M j, Y g:i A', strtotime($r['transaction_date']));
            }
            echo json_encode($rows);
            break;

        case 'payments':
            $stmt = $db->prepare(
                'SELECT p.payment_id, p.phone, p.amount, p.status,
                        p.mpesa_receipt, p.result_desc, p.created_at,
                        g.goal_name
                 FROM mpesa_payments p
                 LEFT JOIN savings_goals g ON p.goal_id = g.goal_id
                 WHERE p.user_id = ?
                 ORDER BY p.created_at DESC
                 LIMIT 50'
            );
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$r) {
                $r['amount']     = number_format((float)$r['amount'], 2);
                $r['created_at'] = date('M j, Y g:i A', strtotime($r['created_at']));
            }
            echo json_encode($rows);
            break;

        case 'summary':
        default:
            // Total M-Pesa spend imported
            $s1 = $db->prepare(
                "SELECT COUNT(*) as tx_count,
                        COALESCE(SUM(amount), 0) as total_mpesa
                 FROM mpesa_transactions
                 WHERE user_id = ? AND transaction_type != 'received'"
            );
            $s1->execute([$user_id]);
            $summary = $s1->fetch();

            // Successful STK pushes this month
            $s2 = $db->prepare(
                "SELECT COUNT(*) as payments_this_month,
                        COALESCE(SUM(amount), 0) as paid_this_month
                 FROM mpesa_payments
                 WHERE user_id = ? AND status = 'completed'
                   AND MONTH(created_at) = MONTH(CURDATE())
                   AND YEAR(created_at) = YEAR(CURDATE())"
            );
            $s2->execute([$user_id]);
            $monthly = $s2->fetch();

            // Last 5 transactions for widget preview
            $s3 = $db->prepare(
                'SELECT mpesa_code, transaction_type, amount, counterparty, transaction_date
                 FROM mpesa_transactions
                 WHERE user_id = ?
                 ORDER BY transaction_date DESC
                 LIMIT 5'
            );
            $s3->execute([$user_id]);
            $recent = $s3->fetchAll();
            foreach ($recent as &$r) {
                $r['amount']           = number_format((float)$r['amount'], 2);
                $r['transaction_date'] = date('M j, Y', strtotime($r['transaction_date']));
            }

            echo json_encode([
                'tx_count'           => (int)$summary['tx_count'],
                'total_mpesa'        => number_format((float)$summary['total_mpesa'], 2),
                'payments_this_month'=> (int)$monthly['payments_this_month'],
                'paid_this_month'    => number_format((float)$monthly['paid_this_month'], 2),
                'recent'             => $recent,
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
