<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/login.html');
    exit();
}

$email = $_POST['email'] ?? '';

if (empty($email)) {
    $_SESSION['error'] = 'Email is required';
    header('Location: ../../frontend/login.html');
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
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token in database
        $token_query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)
                        ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at";
        $token_stmt = $db->prepare($token_query);
        $token_stmt->bindParam(':user_id', $user['user_id']);
        $token_stmt->bindParam(':token', $reset_token);
        $token_stmt->bindParam(':expires_at', $expires_at);
        $token_stmt->execute();
        
        // In a real application, you would send an email here
        // For demo purposes, we'll just show the reset link
        $_SESSION['success'] = 'Password reset instructions have been sent to your email. 
                               <br><strong>Demo Reset Link:</strong> 
                               <a href="reset_password.php?token=' . $reset_token . '">Click here to reset password</a>';
    } else {
        // Don't reveal if email exists or not for security
        $_SESSION['success'] = 'If an account with that email exists, password reset instructions have been sent.';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/login.html');
exit();
?>