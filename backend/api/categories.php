<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $user_id = $_SESSION['user_id'];
    
    // Get all categories for the user (including default ones)
    // Mark user-specific categories to distinguish them
    $query = "SELECT category_id as id, 
                     category_name as name,
                     CASE WHEN user_id IS NULL THEN 0 ELSE 1 END as user_specific
              FROM categories 
              WHERE user_id = :user_id OR user_id IS NULL 
              ORDER BY user_specific DESC, category_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>