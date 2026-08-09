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

    // User info
    $stmt = $db->prepare(
        "SELECT user_id, name, email, created_at FROM users WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }

    // Stats — separate queries to avoid multi-join counting issues
    $stmt = $db->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_expenses = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_spent = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $budgets_set = (int) $stmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM categories WHERE user_id = ? AND user_id IS NOT NULL"
    );
    $stmt->execute([$user_id]);
    $custom_categories = (int) $stmt->fetchColumn();

    $days = (new DateTime())->diff(new DateTime($user['created_at']))->days;

    echo json_encode([
        'user_id'         => $user['user_id'],
        'name'            => $user['name'],
        'email'           => $user['email'],
        'member_since'    => date('M d, Y', strtotime($user['created_at'])),
        'days_registered' => $days,
        'stats' => [
            'total_expenses'    => $total_expenses,
            'total_spent'       => number_format($total_spent, 2),
            'budgets_set'       => $budgets_set,
            'custom_categories' => $custom_categories,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
