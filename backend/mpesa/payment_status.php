<?php
/**
 * GET /backend/mpesa/payment_status.php?payment_id=X
 *
 * Strategy for FAST response:
 * 1. Read current status from DB — return it instantly
 * 2. If still pending: spawn a background process to query Daraja (non-blocking)
 * 3. Background process updates the DB
 * 4. Next frontend poll sees the updated status
 *
 * This means the HTTP response is instant (<5ms) every time.
 * The Daraja query (~1s) happens in the background and doesn't block the response.
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$payment_id = (int)($_GET['payment_id'] ?? 0);
$user_id    = (int)$_SESSION['user_id'];

if (!$payment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'payment_id required']);
    exit;
}

try {
    $db = (new Database())->connect();

    $stmt = $db->prepare(
        'SELECT payment_id, status, mpesa_receipt, result_desc,
                checkout_request_id, amount, created_at, updated_at
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

    // If pending, kick off background Daraja query so NEXT poll gets updated status
    if ($payment['status'] === 'pending' && !empty($payment['checkout_request_id'])) {
        $age = time() - strtotime($payment['created_at']);

        // Only query Safaricom after 5 seconds (STK push takes a few seconds to register)
        // Don't query more often than every 5s to avoid rate limiting
        $last_check_age = time() - strtotime($payment['updated_at']);

        if ($age >= 5 && $last_check_age >= 4) {
            // Update updated_at to prevent duplicate background queries
            $db->prepare('UPDATE mpesa_payments SET updated_at = NOW() WHERE payment_id = ?')
               ->execute([$payment_id]);

            // Spawn background process — does NOT block this response
            $script = __DIR__ . '/query_worker.php';
            $cmd = "php8.3 " . escapeshellarg($script) . " " . escapeshellarg($payment_id) . " > /dev/null 2>&1 &";
            shell_exec($cmd);
        }
    }

    // Return current DB status immediately — no waiting for Daraja
    echo json_encode([
        'payment_id'  => (int)$payment['payment_id'],
        'status'      => $payment['status'],
        'receipt'     => $payment['mpesa_receipt'],
        'amount'      => number_format((float)$payment['amount'], 2),
        'description' => $payment['result_desc'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
