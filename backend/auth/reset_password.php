<?php
session_start();
require_once '../config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = 'Invalid reset token';
    header('Location: ../../frontend/login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters';
    } else {
        try {
            $database = new Database();
            $db = $database->connect();
            
            // Verify token and get user
            $query = "SELECT pr.user_id FROM password_resets pr 
                      WHERE pr.token = :token AND pr.expires_at > NOW()";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE users SET password = :password WHERE user_id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':user_id', $user['user_id']);
                $update_stmt->execute();
                
                // Delete used token
                $delete_query = "DELETE FROM password_resets WHERE token = :token";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(':token', $token);
                $delete_stmt->execute();
                
                $_SESSION['success'] = 'Password reset successfully! You can now login with your new password.';
                header('Location: ../../frontend/login.html');
                exit();
            } else {
                $_SESSION['error'] = 'Invalid or expired reset token';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'An error occurred. Please try again.';
        }
    }
}

// Verify token is valid before showing form
try {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT pr.user_id FROM password_resets pr 
              WHERE pr.token = :token AND pr.expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = 'Invalid or expired reset token';
        header('Location: ../../frontend/login.html');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: ../../frontend/login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Reset Password</h2>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" required>
                                <small class="form-text text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="../../frontend/login.html" class="text-decoration-none">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>