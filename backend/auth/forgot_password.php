<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/login.php');
    exit();
}

$email = $_POST['email'] ?? '';

if (empty($email)) {
    $_SESSION['error'] = 'Email is required';
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if email exists
    $query = "SELECT user_id, name FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600);
        
        // Store reset token — use positional params to avoid PDO duplicate-name bug
        $token_query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE token = ?, expires_at = ?";
        $token_stmt = $db->prepare($token_query);
        $token_stmt->execute([$user['user_id'], $reset_token, $expires_at, $reset_token, $expires_at]);
        
        // In production, send the reset link by email.
        // For this demo the link is stored in session so it can be shown on the login page.
        $reset_url = '../../backend/auth/reset_password.php?token=' . $reset_token;
        $_SESSION['reset_link'] = $reset_url;
        $_SESSION['success'] = 'Password reset link generated. See the demo link below.';
    } else {
        // Don't reveal if email exists or not for security
        $_SESSION['success'] = 'If an account with that email exists, password reset instructions have been sent.';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/login.php');
exit();
?>