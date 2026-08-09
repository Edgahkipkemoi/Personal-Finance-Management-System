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

    // user_specific returned as int 1/0 — JS must use == 1, not === true
    $stmt = $db->prepare(
        "SELECT category_id AS id,
                category_name AS name,
                CASE WHEN user_id IS NULL THEN 0 ELSE 1 END AS user_specific
         FROM categories
         WHERE user_id = ? OR user_id IS NULL
         ORDER BY user_specific DESC, category_name ASC"
    );
    $stmt->execute([$user_id]);

    echo json_encode($stmt->fetchAll());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
