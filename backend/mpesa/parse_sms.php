<?php
/**
 * POST /backend/mpesa/parse_sms.php
 * Parses raw M-Pesa SMS text(s) into structured transactions,
 * auto-categorises them, and optionally imports as expenses.
 *
 * Request JSON:
 *   { "messages": ["SMS text 1", "SMS text 2"], "import": true }
 *
 * Response JSON:
 *   { "parsed": [ { transaction fields... } ], "imported": 2, "duplicates": 0 }
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$messages = $input['messages'] ?? [];
$doImport = (bool) ($input['import'] ?? false);
$user_id  = (int) $_SESSION['user_id'];

if (empty($messages) || !is_array($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'Provide a "messages" array of M-Pesa SMS texts']);
    exit;
}

// ── SMS Pattern Library ───────────────────────────────────────────────────────
// Each pattern extracts: code, type, amount, counterparty, date, balance
$patterns = [
    // Sent money to person:
    // "ABC123DE confirmed. Ksh1,000.00 sent to JOHN DOE 07XXXXXXXX on 1/8/26 at 10:30 AM. New M-PESA balance is Ksh5,000.00."
    'sent' => [
        'regex' => '/^([A-Z0-9]{10})\s+confirmed\.\s+Ksh([\d,]+(?:\.\d{2})?)\s+sent to\s+(.+?)\s+\d{7,}\s+on\s+([\d\/]+)\s+at\s+([\d:]+ [AP]M).*balance is Ksh([\d,]+(?:\.\d{2})?)/im',
        'type'  => 'sent',
    ],
    // Received money:
    // "ABC123DE confirmed. You have received Ksh500.00 from JANE ROE 07XXXXXXXX on 1/8/26..."
    'received' => [
        'regex' => '/^([A-Z0-9]{10})\s+confirmed\.\s+You have received\s+Ksh([\d,]+(?:\.\d{2})?)\s+from\s+(.+?)\s+\d{7,}\s+on\s+([\d\/]+)\s+at\s+([\d:]+ [AP]M).*balance is Ksh([\d,]+(?:\.\d{2})?)/im',
        'type'  => 'received',
    ],
    // Paybill:
    // "ABC123DE confirmed. Ksh2,500.00 paid to KPLC PREPAID for account 0000000000 on 1/8/26..."
    'paybill' => [
        'regex' => '/^([A-Z0-9]{10})\s+confirmed\.\s+Ksh([\d,]+(?:\.\d{2})?)\s+paid to\s+(.+?)\s+for account.*on\s+([\d\/]+)\s+at\s+([\d:]+ [AP]M).*balance is Ksh([\d,]+(?:\.\d{2})?)/im',
        'type'  => 'paybill',
    ],
    // Buy Goods (Till):
    // "ABC123DE confirmed. Ksh350.00 paid to NAIVAS SUPERMARKET. on 1/8/26..."
    'till' => [
        'regex' => '/^([A-Z0-9]{10})\s+confirmed\.\s+Ksh([\d,]+(?:\.\d{2})?)\s+paid to\s+(.+?)\.\s+on\s+([\d\/]+)\s+at\s+([\d:]+ [AP]M).*balance is Ksh([\d,]+(?:\.\d{2})?)/im',
        'type'  => 'till',
    ],
    // Withdrawal:
    // "ABC123DE confirmed on 1/8/26 at 10:00 AM. Ksh1,000.00 withdrawn from agent..."
    'withdrawal' => [
        'regex' => '/^([A-Z0-9]{10})\s+confirmed on\s+([\d\/]+)\s+at\s+([\d:]+ [AP]M).*Ksh([\d,]+(?:\.\d{2})?)\s+withdrawn.*balance is Ksh([\d,]+(?:\.\d{2})?)/im',
        'type'  => 'withdrawal',
    ],
    // Airtime:
    // "confirmed. You bought Ksh100.00 of airtime on 1/8/26"
    'airtime' => [
        'regex' => '/([A-Z0-9]{10}).*You bought\s+Ksh([\d,]+(?:\.\d{2})?)\s+of airtime.*on\s+([\d\/]+)/im',
        'type'  => 'airtime',
    ],
];

// ── Auto-categorisation map ───────────────────────────────────────────────────
$categoryMap = [
    'received'   => null,   // Income — don't auto-import as expense
    'kplc'       => 'Bills & Utilities',
    'nairobi water' => 'Bills & Utilities',
    'zuku'       => 'Bills & Utilities',
    'safaricom'  => 'Bills & Utilities',
    'uber'       => 'Transportation',
    'bolt'       => 'Transportation',
    'naivas'     => 'Food & Dining',
    'quickmart'  => 'Food & Dining',
    'carrefour'  => 'Shopping',
    'java'       => 'Food & Dining',
    'airtime'    => 'Bills & Utilities',
    'withdrawal' => 'Other',
    'default'    => 'Other',
];

function parseMoney(string $s): float {
    return (float) str_replace(',', '', $s);
}

function parseDate(string $d, string $t = ''): string {
    $str = trim($d . ' ' . $t);
    $ts  = strtotime($str);
    return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
}

function guessCategory(string $counterparty, string $type, array $map): string {
    if ($type === 'received') return 'received';
    $cp = strtolower($counterparty);
    foreach ($map as $keyword => $cat) {
        if ($keyword !== 'default' && $keyword !== 'received' && str_contains($cp, $keyword)) {
            return $cat;
        }
    }
    if (isset($map[$type])) return $map[$type];
    return $map['default'];
}

// ── Parse each SMS ────────────────────────────────────────────────────────────
$parsed = [];
foreach ($messages as $sms) {
    $sms     = trim($sms);
    $matched = false;

    foreach ($patterns as $key => $p) {
        if (!preg_match($p['regex'], $sms, $m)) continue;
        $matched = true;

        if ($key === 'withdrawal') {
            $tx = [
                'code'         => $m[1],
                'type'         => 'withdrawal',
                'amount'       => parseMoney($m[4]),
                'counterparty' => 'ATM/Agent Withdrawal',
                'date'         => parseDate($m[2], $m[3]),
                'balance'      => parseMoney($m[5]),
                'raw'          => $sms,
            ];
        } elseif ($key === 'airtime') {
            $tx = [
                'code'         => $m[1],
                'type'         => 'airtime',
                'amount'       => parseMoney($m[2]),
                'counterparty' => 'Safaricom Airtime',
                'date'         => parseDate($m[3]),
                'balance'      => 0,
                'raw'          => $sms,
            ];
        } else {
            $tx = [
                'code'         => $m[1],
                'type'         => $p['type'],
                'amount'       => parseMoney($m[2]),
                'counterparty' => trim($m[3]),
                'date'         => parseDate($m[4], $m[5]),
                'balance'      => isset($m[6]) ? parseMoney($m[6]) : 0,
                'raw'          => $sms,
            ];
        }

        $tx['category'] = guessCategory($tx['counterparty'], $tx['type'], $categoryMap);
        $tx['importable'] = $tx['type'] !== 'received';
        $parsed[] = $tx;
        break;
    }

    if (!$matched) {
        $parsed[] = [
            'code'         => null,
            'type'         => 'unknown',
            'amount'       => 0,
            'counterparty' => 'Unrecognised format',
            'date'         => date('Y-m-d H:i:s'),
            'balance'      => 0,
            'raw'          => $sms,
            'category'     => 'Other',
            'importable'   => false,
        ];
    }
}

// ── Import into DB if requested ───────────────────────────────────────────────
$imported   = 0;
$duplicates = 0;

if ($doImport) {
    try {
        $db = (new Database())->connect();

        // Load category name → id map
        $catStmt = $db->prepare(
            'SELECT category_id, category_name FROM categories
             WHERE user_id IS NULL OR user_id = ?'
        );
        $catStmt->execute([$user_id]);
        $catMap = [];
        foreach ($catStmt->fetchAll() as $row) {
            $catMap[strtolower($row['category_name'])] = $row['category_id'];
        }

        foreach ($parsed as &$tx) {
            if (!$tx['importable'] || !$tx['code'] || $tx['type'] === 'unknown') continue;

            // De-duplicate by mpesa_code + user
            $dup = $db->prepare(
                'SELECT transaction_id FROM mpesa_transactions
                 WHERE mpesa_code = ? AND user_id = ?'
            );
            $dup->execute([$tx['code'], $user_id]);
            if ($dup->fetch()) {
                $tx['status'] = 'duplicate';
                $duplicates++;
                continue;
            }

            $catId = $catMap[strtolower($tx['category'])] ?? null;

            // 1. Insert into mpesa_transactions
            $db->prepare(
                'INSERT INTO mpesa_transactions
                 (user_id, mpesa_code, transaction_type, amount, counterparty,
                  transaction_date, balance_after, raw_sms)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $user_id,
                $tx['code'],
                $tx['type'],
                $tx['amount'],
                $tx['counterparty'],
                $tx['date'],
                $tx['balance'] ?: null,
                $tx['raw'],
            ]);
            $txId = $db->lastInsertId();

            // 2. Insert into expenses
            $db->prepare(
                'INSERT INTO expenses
                 (amount, description, expense_date, category_id, user_id)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $tx['amount'],
                'M-Pesa: ' . $tx['counterparty'] . ' [' . $tx['code'] . ']',
                date('Y-m-d', strtotime($tx['date'])),
                $catId,
                $user_id,
            ]);
            $expenseId = $db->lastInsertId();

            // 3. Link expense back to mpesa_transaction
            $db->prepare(
                'UPDATE mpesa_transactions SET expense_id = ? WHERE transaction_id = ?'
            )->execute([$expenseId, $txId]);

            $tx['status'] = 'imported';
            $imported++;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Import error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode([
    'parsed'     => $parsed,
    'imported'   => $imported,
    'duplicates' => $duplicates,
    'total'      => count($parsed),
]);
