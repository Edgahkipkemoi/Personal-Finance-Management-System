<?php
/**
 * POST /backend/mpesa/stk_push.php
 * Initiates an M-Pesa STK Push to fund a savings goal.
 *
 * Request JSON:
 *   { "phone": "2547XXXXXXXX", "amount": 500, "goal_id": 3 }
 *
 * Response JSON:
 *   { "success": true, "message": "...", "payment_id": 12, "checkout_request_id": "..." }
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MpesaService.php';

header('Content-Type: application/json');

// Auth guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$user_id = (int) $_SESSION['user_id'];
$phone   = trim($input['phone'] ?? '');
$amount  = (float) ($input['amount'] ?? 0);
$goal_id = isset($input['goal_id']) ? (int) $input['goal_id'] : null;

// ── Validation ────────────────────────────────────────────────────────────────
if (!preg_match('/^2547\d{8}$|^2541\d{8}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Enter a valid Safaricom number in format 2547XXXXXXXX']);
    exit;
}
if ($amount < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Minimum amount is KSh 1']);
    exit;
}
if ($amount > 150000) {
    http_response_code(400);
    echo json_encode(['error' => 'Maximum M-Pesa transaction is KSh 150,000']);
    exit;
}

try {
    $db = (new Database())->connect();

    // Verify goal ownership if provided
    $goal_name = 'Savings';
    if ($goal_id) {
        $gs = $db->prepare('SELECT goal_name FROM savings_goals WHERE goal_id = ? AND user_id = ?');
        $gs->execute([$goal_id, $user_id]);
        $goal = $gs->fetch();
        if (!$goal) {
            http_response_code(404);
            echo json_encode(['error' => 'Goal not found']);
            exit;
        }
        $goal_name = $goal['goal_name'];
    }

    // Insert pending payment record
    $ins = $db->prepare(
        'INSERT INTO mpesa_payments (user_id, goal_id, phone, amount, status)
         VALUES (?, ?, ?, ?, "pending")'
    );
    $ins->execute([$user_id, $goal_id, $phone, $amount]);
    $payment_id = $db->lastInsertId();

    // ── Call Daraja STK Push ─────────────────────────────────────────────────
    $mpesa   = new MpesaService();
    $ref     = 'Goal-' . ($goal_id ?? 'Save');
    $desc    = 'PFM Goal Fund';
    $result  = $mpesa->stkPush($phone, $amount, $ref, $desc);

    if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        // Success — store the request IDs
        $upd = $db->prepare(
            'UPDATE mpesa_payments
             SET checkout_request_id = ?, merchant_request_id = ?
             WHERE payment_id = ?'
        );
        $upd->execute([
            $result['CheckoutRequestID'],
            $result['MerchantRequestID'],
            $payment_id
        ]);

        echo json_encode([
            'success'             => true,
            'message'             => 'M-Pesa prompt sent to ' . $phone . '. Enter your PIN to complete.',
            'payment_id'          => $payment_id,
            'checkout_request_id' => $result['CheckoutRequestID'],
        ]);
    } else {
        // Daraja rejected the request
        $errMsg = $result['errorMessage'] ?? ($result['ResponseDescription'] ?? 'M-Pesa request failed');
        $db->prepare('UPDATE mpesa_payments SET status = "failed", result_desc = ? WHERE payment_id = ?')
           ->execute([$errMsg, $payment_id]);

        http_response_code(502);
        echo json_encode(['error' => $errMsg]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
