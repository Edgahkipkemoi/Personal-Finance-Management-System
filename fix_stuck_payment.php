<?php
/**
 * Fix Stuck M-Pesa Payments
 * 
 * This script checks for pending payments, queries Safaricom, 
 * and processes successful payments that weren't auto-detected.
 * 
 * Usage: php8.3 fix_stuck_payment.php
 */

require_once 'backend/config/database.php';
require_once 'backend/config/mpesa.php';
require_once 'backend/mpesa/MpesaService.php';

try {
    $db = (new Database())->connect();
    
    // Find all pending payments
    $stmt = $db->query(
        "SELECT payment_id, user_id, goal_id, amount, phone, checkout_request_id, created_at
         FROM mpesa_payments 
         WHERE status = 'pending' 
         AND checkout_request_id IS NOT NULL
         ORDER BY payment_id DESC"
    );
    $pending = $stmt->fetchAll();
    
    if (empty($pending)) {
        echo "✅ No stuck payments found. All payments are up to date!\n";
        exit(0);
    }
    
    echo "Found " . count($pending) . " pending payment(s). Checking with Safaricom...\n\n";
    
    $mpesa = new MpesaService();
    $processed = 0;
    $failed = 0;
    
    foreach ($pending as $payment) {
        echo "Checking Payment ID {$payment['payment_id']} (KSh {$payment['amount']})...\n";
        
        try {
            $result = $mpesa->stkQuery($payment['checkout_request_id']);
            $code = (int)($result['ResultCode'] ?? -1);
            
            if ($code === 0) {
                // Payment was successful!
                echo "  ✅ SUCCESSFUL - Processing...\n";
                
                $receipt = $result['MpesaReceiptNumber'] ?? 'SKH' . strtoupper(substr(md5($payment['checkout_request_id']), 0, 8));
                
                // Update payment
                $db->prepare(
                    'UPDATE mpesa_payments 
                     SET status = "completed", mpesa_receipt = ?, result_desc = "Success (auto-fixed)", updated_at = NOW()
                     WHERE payment_id = ?'
                )->execute([$receipt, $payment['payment_id']]);
                
                // Goal contribution if goal_id exists
                if ($payment['goal_id']) {
                    $checkContrib = $db->prepare(
                        'SELECT COUNT(*) as cnt FROM goal_contributions 
                         WHERE goal_id = ? AND description LIKE ?'
                    );
                    $checkContrib->execute([$payment['goal_id'], '%' . $receipt . '%']);
                    
                    if ($checkContrib->fetch()['cnt'] == 0) {
                        $db->prepare(
                            'INSERT INTO goal_contributions 
                             (goal_id, user_id, amount, contribution_date, description)
                             VALUES (?, ?, ?, CURDATE(), ?)'
                        )->execute([$payment['goal_id'], $payment['user_id'], $payment['amount'], 'M-Pesa ' . $receipt]);
                        
                        $db->prepare(
                            'UPDATE savings_goals SET current_amount = (
                                SELECT COALESCE(SUM(amount), 0) FROM goal_contributions WHERE goal_id = ?
                             ) WHERE goal_id = ?'
                        )->execute([$payment['goal_id'], $payment['goal_id']]);
                        
                        echo "     → Goal contribution recorded\n";
                    }
                }
                
                // Create expense
                $checkExpense = $db->prepare(
                    'SELECT COUNT(*) as cnt FROM expenses WHERE user_id = ? AND description LIKE ?'
                );
                $checkExpense->execute([$payment['user_id'], '%' . $receipt . '%']);
                
                if ($checkExpense->fetch()['cnt'] == 0) {
                    $catStmt = $db->prepare(
                        "SELECT category_id FROM categories
                         WHERE (category_name = 'Savings' OR category_name = 'Bills & Utilities') 
                         AND (user_id = ? OR user_id IS NULL) ORDER BY user_id DESC LIMIT 1"
                    );
                    $catStmt->execute([$payment['user_id']]);
                    $cat = $catStmt->fetch();
                    
                    $db->prepare(
                        'INSERT INTO expenses (amount, description, expense_date, category_id, user_id)
                         VALUES (?, ?, CURDATE(), ?, ?)'
                    )->execute([
                        $payment['amount'],
                        'M-Pesa ' . $receipt . ($payment['goal_id'] ? ' (Goal funding)' : ''),
                        $cat ? $cat['category_id'] : null,
                        $payment['user_id']
                    ]);
                    
                    echo "     → Expense created\n";
                }
                
                echo "  ✅ Receipt: $receipt\n\n";
                $processed++;
                
            } elseif ($code > 0) {
                // Payment failed or cancelled
                echo "  ❌ FAILED - " . ($result['ResultDesc'] ?? 'Unknown error') . "\n\n";
                $status = ($code === 1032) ? 'cancelled' : 'failed';
                $db->prepare(
                    'UPDATE mpesa_payments SET status = ?, result_desc = ?, updated_at = NOW() WHERE payment_id = ?'
                )->execute([$status, $result['ResultDesc'] ?? 'Failed', $payment['payment_id']]);
                $failed++;
            } else {
                echo "  ⏳ Still pending at Safaricom\n\n";
            }
            
        } catch (Exception $e) {
            echo "  ⚠️  Query error: " . $e->getMessage() . "\n\n";
        }
        
        sleep(1); // Be nice to the API
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Summary:\n";
    echo "  ✅ Processed: $processed\n";
    echo "  ❌ Failed: $failed\n";
    echo "  Total checked: " . count($pending) . "\n";
    echo "\n✅ DONE! Refresh your browser to see updates.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
