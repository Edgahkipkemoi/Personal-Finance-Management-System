<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT user_id, name, password FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: ../dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid password';
        }
    } else {
        $_SESSION['error'] = 'User not found';
    }
    
    header('Location: ../index.php');
    exit();
}
?>