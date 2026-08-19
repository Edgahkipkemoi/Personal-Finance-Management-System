<?php
/**
 * Background worker — called by payment_status.php via shell_exec()
 * Queries Daraja for payment status and updates the database.
 * This runs as a separate process so it never blocks the HTTP response.
 *
 * Usage: php8.3 query_worker.php <payment_id>
 */

if (PHP_SAPI !== 'cli') {
    exit; // Only run from CLI
}

$payment_id = (int)($argv[1] ?? 0);
if (!$payment_id) {
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MpesaService.php';

function processSuccess(PDO $db, int $payment_id, string $receipt, float $amount, int $user_id, ?int $goal_id): void {
    // 1. Mark payment completed
    $db->prepare(
        'UPDATE mpesa_payments 
         SET status = "completed", mpesa_receipt = ?, result_desc = "Success", updated_at = NOW()
         WHERE payment_id = ?'
    )->execute([$receipt, $payment_id]);

    // 2. Goal contribution
    if ($goal_id) {
        $exists = $db->prepare(
            'SELECT COUNT(*) FROM goal_contributions WHERE goal_id = ? AND description LIKE ?'
        );
        $exists->execute([$goal_id, '%' . $receipt . '%']);
        if (!$exists->fetchColumn()) {
            $db->prepare(
                'INSERT INTO goal_contributions (goal_id, user_id, amount, contribution_date, description)
                 VALUES (?, ?, ?, CURDATE(), ?)'
            )->execute([$goal_id, $user_id, $amount, 'M-Pesa ' . $receipt]);

            $db->prepare(
                'UPDATE savings_goals
                 SET current_amount = (SELECT COALESCE(SUM(amount),0) FROM goal_contributions WHERE goal_id = ?)
                 WHERE goal_id = ?'
            )->execute([$goal_id, $goal_id]);
        }
    }

    // 3. Expense
    $expExists = $db->prepare(
        'SELECT COUNT(*) FROM expenses WHERE user_id = ? AND description LIKE ?'
    );
    $expExists->execute([$user_id, '%' . $receipt . '%']);
    if (!$expExists->fetchColumn()) {
        $catStmt = $db->prepare(
            "SELECT category_id FROM categories
             WHERE (category_name = 'Savings' OR category_name = 'Bills & Utilities')
             AND (user_id = ? OR user_id IS NULL)
             ORDER BY user_id DESC LIMIT 1"
        );
        $catStmt->execute([$user_id]);
        $cat = $catStmt->fetch();

        $db->prepare(
            'INSERT INTO expenses (amount, description, expense_date, category_id, user_id)
             VALUES (?, ?, CURDATE(), ?, ?)'
        )->execute([
            $amount,
            'M-Pesa ' . $receipt . ($goal_id ? ' (Goal funding)' : ''),
            $cat ? $cat['category_id'] : null,
            $user_id,
        ]);
    }

    error_log("[WORKER] Payment $payment_id: SUCCESS — $receipt");
}

try {
    $db = (new Database())->connect();

    // Re-check it's still pending (another worker may have already processed it)
    $stmt = $db->prepare(
        'SELECT payment_id, user_id, goal_id, amount, checkout_request_id, status
         FROM mpesa_payments WHERE payment_id = ?'
    );
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if (!$payment || $payment['status'] !== 'pending' || empty($payment['checkout_request_id'])) {
        exit; // Already processed
    }

    $mpesa  = new MpesaService();
    $result = $mpesa->stkQuery($payment['checkout_request_id']);
    $code   = (int)($result['ResultCode'] ?? -1);

    if ($code === 0) {
        $receipt = $result['MpesaReceiptNumber']
            ?? 'SKH' . strtoupper(substr(md5($payment['checkout_request_id']), 0, 8));
        processSuccess(
            $db,
            $payment_id,
            $receipt,
            (float)$payment['amount'],
            (int)$payment['user_id'],
            $payment['goal_id'] ? (int)$payment['goal_id'] : null
        );

    } elseif (isset($result['ResultCode']) && $code !== 0 && $code !== -1) {
        $desc   = $result['ResultDesc'] ?? 'Failed';
        $status = ($code === 1032) ? 'cancelled' : 'failed';
        $db->prepare(
            'UPDATE mpesa_payments SET status = ?, result_desc = ?, updated_at = NOW()
             WHERE payment_id = ?'
        )->execute([$status, $desc, $payment_id]);

        error_log("[WORKER] Payment $payment_id: $status — $desc");
    }
    // If code === -1 (no result yet), do nothing — will retry next poll

} catch (Exception $e) {
    error_log('[WORKER ERROR] ' . $e->getMessage());
}
