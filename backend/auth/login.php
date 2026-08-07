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

$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email and password are required';
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    $database = new Database();
    $db       = $database->connect();

    $query = "SELECT user_id, name, password FROM users WHERE email = :email";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Use a single generic message to prevent user enumeration
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID after successful login (prevents session fixation)
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];

        header('Location: ../../frontend/dashboard.html');
        exit();
    } else {
        $_SESSION['error'] = 'Invalid email or password';
    }

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/login.php');
exit();
