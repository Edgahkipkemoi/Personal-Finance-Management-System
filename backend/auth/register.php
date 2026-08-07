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

$name     = trim($_POST['name']     ?? '');
$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: ../../frontend/login.php');
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters';
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    $database = new Database();
    $db       = $database->connect();

    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = :email";
    $check_stmt  = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Email already registered';
        header('Location: ../../frontend/login.php');
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':name',     $name);
    $stmt->bindParam(':email',    $email);
    $stmt->bindParam(':password', $hashed_password);

    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();

        // Create default categories for new user
        $default_categories = [
            'Food & Dining', 'Transportation', 'Shopping', 'Entertainment',
            'Bills & Utilities', 'Healthcare', 'Education', 'Other'
        ];
        $cat_query = "INSERT INTO categories (category_name, user_id) VALUES (:category_name, :user_id)";
        $cat_stmt  = $db->prepare($cat_query);

        foreach ($default_categories as $category) {
            $cat_stmt->bindParam(':category_name', $category);
            $cat_stmt->bindParam(':user_id',       $user_id);
            $cat_stmt->execute();
        }

        // Regenerate session ID after successful registration / login
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['success']   = 'Registration successful!';

        header('Location: ../../frontend/dashboard.html');
        exit();
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
    }

} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: ../../frontend/login.php');
exit();
