<?php
/**
 * GET /backend/mpesa/payment_status.php?payment_id=X
 * Polls payment status. Frontend polls this after STK push.
 * Also triggers a live Daraja STK query if still pending after 10 s.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MpesaService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$payment_id = (int) ($_GET['payment_id'] ?? 0);
$user_id    = (int) $_SESSION['user_id'];

if (!$payment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'payment_id required']);
    exit;
}

try {
    $db = (new Database())->connect();

    $stmt = $db->prepare(
        'SELECT payment_id, status, mpesa_receipt, result_desc,
                checkout_request_id, amount, created_at
         FROM mpesa_payments
         WHERE payment_id = ? AND user_id = ?'
    );
    $stmt->execute([$payment_id, $user_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }

    // If still pending and checkout_request_id exists, query Daraja for live status
    if ($payment['status'] === 'pending' && !empty($payment['checkout_request_id'])) {
        $age = time() - strtotime($payment['created_at']);
        if ($age >= 10) {
            try {
                $mpesa  = new MpesaService();
                $result = $mpesa->stkQuery($payment['checkout_request_id']);
                $code   = (int) ($result['ResultCode'] ?? -1);

                if ($code === 0) {
                    $db->prepare(
                        'UPDATE mpesa_payments SET status = "completed" WHERE payment_id = ?'
                    )->execute([$payment_id]);
                    $payment['status'] = 'completed';
                } elseif (isset($result['ResultCode']) && $code !== 0) {
                    $desc = $result['ResultDesc'] ?? 'Failed';
                    $db->prepare(
                        'UPDATE mpesa_payments SET status = "failed", result_desc = ? WHERE payment_id = ?'
                    )->execute([$desc, $payment_id]);
                    $payment['status'] = 'failed';
                    $payment['result_desc'] = $desc;
                }
            } catch (Exception $e) {
                // Daraja query failed — not critical, just return DB status
            }
        }
    }

    echo json_encode([
        'payment_id'  => $payment['payment_id'],
        'status'      => $payment['status'],
        'receipt'     => $payment['mpesa_receipt'],
        'amount'      => number_format((float)$payment['amount'], 2),
        'description' => $payment['result_desc'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
