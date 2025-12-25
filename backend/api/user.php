<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and return user info
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'User'
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
?>