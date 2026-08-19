<?php
/**
 * M-Pesa STK Push Callback
 * Safaricom POSTs the payment result here after the user enters their PIN.
 *
 * This endpoint must be publicly accessible (use ngrok during development).
 * URL is set in MpesaConfig::CALLBACK_URL
 */

require_once __DIR__ . '/../config/database.php';

// Always respond 200 immediately — Daraja retries if it gets anything else
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

// Read the callback body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log raw payload for debugging
error_log('[MPESA CALLBACK] ' . $raw);

if (empty($data['Body']['stkCallback'])) {
    exit; // Malformed — nothing to do
}

$cb             = $data['Body']['stkCallback'];
$merchantReqId  = $cb['MerchantRequestID']  ?? '';
$checkoutReqId  = $cb['CheckoutRequestID']  ?? '';
$resultCode     = (int) ($cb['ResultCode']  ?? -1);
$resultDesc     = $cb['ResultDesc']         ?? '';

try {
    $db = (new Database())->connect();

    // Find the pending payment by CheckoutRequestID
    $stmt = $db->prepare(
        'SELECT payment_id, user_id, goal_id, amount
         FROM mpesa_payments
         WHERE checkout_request_id = ? AND status = "pending"
         LIMIT 1'
    );
    $stmt->execute([$checkoutReqId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        exit; // Unknown transaction — ignore
    }

    if ($resultCode === 0) {
        // ── PAYMENT SUCCESSFUL ───────────────────────────────────────────────
        $items   = $cb['CallbackMetadata']['Item'] ?? [];
        $meta    = [];
        foreach ($items as $item) {
            $meta[$item['Name']] = $item['Value'] ?? null;
        }

        $receipt = $meta['MpesaReceiptNumber'] ?? null;
        $amount  = (float) ($meta['Amount'] ?? $payment['amount']);
        $phone   = $meta['PhoneNumber'] ?? '';

        // 1. Mark payment completed
        $db->prepare(
            'UPDATE mpesa_payments
             SET status = "completed", mpesa_receipt = ?, result_desc = ?, updated_at = NOW()
             WHERE payment_id = ?'
        )->execute([$receipt, $resultDesc, $payment['payment_id']]);

        // 2. Record as goal contribution if linked to a goal
        if ($payment['goal_id']) {
            // Check if contribution already exists (prevent duplicates)
            $existingContrib = $db->prepare(
                'SELECT COUNT(*) as cnt FROM goal_contributions 
                 WHERE goal_id = ? AND description LIKE ? AND amount = ?'
            );
            $existingContrib->execute([
                $payment['goal_id'],
                '%' . ($receipt ?? 'payment') . '%',
                $amount
            ]);
            $exists = $existingContrib->fetch()['cnt'];
            
            if (!$exists) {
                $db->prepare(
                    'INSERT INTO goal_contributions
                     (goal_id, user_id, amount, contribution_date, description)
                     VALUES (?, ?, ?, CURDATE(), ?)'
                )->execute([
                    $payment['goal_id'],
                    $payment['user_id'],
                    $amount,
                    'M-Pesa ' . ($receipt ?? 'payment')
                ]);

                // Recalculate goal current_amount
                $db->prepare(
                    'UPDATE savings_goals
                     SET current_amount = (
                         SELECT COALESCE(SUM(amount), 0)
                         FROM goal_contributions
                         WHERE goal_id = ?
                     )
                     WHERE goal_id = ?'
                )->execute([$payment['goal_id'], $payment['goal_id']]);
            }
        }

        // 3. Auto-record as an expense (check for duplicates first)
        $existingExpense = $db->prepare(
            'SELECT COUNT(*) as cnt FROM expenses 
             WHERE user_id = ? AND description LIKE ? AND amount = ?'
        );
        $existingExpense->execute([
            $payment['user_id'],
            '%M-Pesa ' . ($receipt ?? '') . '%',
            $amount
        ]);
        $expenseExists = $existingExpense->fetch()['cnt'];
        
        if (!$expenseExists) {
            // Get Savings or Bills category
            $catStmt = $db->prepare(
                "SELECT category_id FROM categories
                 WHERE (category_name = 'Savings' OR category_name = 'Bills & Utilities') 
                 AND (user_id = ? OR user_id IS NULL)
                 ORDER BY user_id DESC LIMIT 1"
            );
            $catStmt->execute([$payment['user_id']]);
            $cat = $catStmt->fetch();

            $db->prepare(
                'INSERT INTO expenses
                 (amount, description, expense_date, category_id, user_id)
                 VALUES (?, ?, CURDATE(), ?, ?)'
            )->execute([
                $amount,
                'M-Pesa ' . ($receipt ?? '') . ($payment['goal_id'] ? ' (Goal funding)' : ''),
                $cat ? $cat['category_id'] : null,
                $payment['user_id'],
            ]);
        }

    } else {
        // ── PAYMENT FAILED / CANCELLED ───────────────────────────────────────
        $status = ($resultCode === 1032) ? 'cancelled' : 'failed';
        $db->prepare(
            'UPDATE mpesa_payments
             SET status = ?, result_desc = ?, updated_at = NOW()
             WHERE payment_id = ?'
        )->execute([$status, $resultDesc, $payment['payment_id']]);
    }

} catch (Exception $e) {
    error_log('[MPESA CALLBACK ERROR] ' . $e->getMessage());
}
