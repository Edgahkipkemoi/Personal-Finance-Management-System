<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $database = new Database();
    $db = $database->connect();
    
    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Email already registered';
        header('Location: ../index.php');
        exit();
    }
    
    // Insert new user
    $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    
    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        // Create default categories for new user
        $categories = ['Food & Dining', 'Transportation', 'Shopping', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Other'];
        $cat_query = "INSERT INTO categories (category_name, user_id) VALUES (:category_name, :user_id)";
        $cat_stmt = $db->prepare($cat_query);
        
        foreach ($categories as $category) {
            $cat_stmt->bindParam(':category_name', $category);
            $cat_stmt->bindParam(':user_id', $user_id);
            $cat_stmt->execute();
        }
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['success'] = 'Registration successful!';
        header('Location: ../dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = 'Registration failed';
        header('Location: ../index.php');
        exit();
    }
}
?>