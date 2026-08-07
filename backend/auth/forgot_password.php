<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/login.php');
    exit();
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ../../frontend/login.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    $_SESSION['error'] = 'Email is required';
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    $database = new Database();
    $db       = $database->connect();

    $query = "SELECT user_id, name FROM users WHERE email = :email";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();

        $reset_token = bin2hex(random_bytes(32));
        $expires_at  = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $token_query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)
                        ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at";
        $token_stmt  = $db->prepare($token_query);
        $token_stmt->bindParam(':user_id',    $user['user_id']);
        $token_stmt->bindParam(':token',      $reset_token);
        $token_stmt->bindParam(':expires_at', $expires_at);
        $token_stmt->execute();

        // In production this would send an email.
        // For the demo we store the link separately so it can be rendered as real HTML
        // without bypassing htmlspecialchars on the generic success message.
        $_SESSION['success']          = 'If an account with that email exists, password reset instructions have been sent.';
        $_SESSION['demo_reset_token'] = $reset_token; // rendered as a link in login.php
    } else {
        // Do not reveal whether the email exists
        $_SESSION['success'] = 'If an account with that email exists, password reset instructions have been sent.';
    }

} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/login.php');
exit();
