<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .login-card { border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .brand-header { background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 20px 20px 0 0; padding: 2rem; text-align: center; color: white; }
        .nav-tabs .nav-link { font-weight: 500; color: #6b7280; }
        .nav-tabs .nav-link.active { color: #667eea; border-bottom: 2px solid #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5 mb-5">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="brand-header">
                        <i class="fas fa-wallet fa-2x mb-2"></i>
                        <h4 class="mb-0 fw-bold">Finance Manager</h4>
                        <p class="mb-0 mt-1" style="opacity:0.85; font-size:0.875rem;">Track expenses, manage budgets, reach your goals</p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php
                        if (isset($_SESSION['error'])) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                            unset($_SESSION['error']);
                        }
                        if (isset($_SESSION['success'])) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                            unset($_SESSION['success']);
                        }
                        // Show demo password reset link if available
                        if (isset($_SESSION['reset_link'])) {
                            $link = htmlspecialchars($_SESSION['reset_link']);
                            echo '<div class="alert alert-info"><strong>Demo Reset Link:</strong> <a href="' . $link . '">Click here to reset your password</a></div>';
                            unset($_SESSION['reset_link']);
                        }
                        ?>
                        
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login">Login</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#register">Register</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- Login Form -->
                            <div class="tab-pane fade show active" id="login">
                                <form action="../backend/auth/login.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                    <div class="text-center mt-3">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-decoration-none">Forgot Password?</a>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Register Form -->
                            <div class="tab-pane fade" id="register">
                                <form action="../backend/auth/register.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                        <small class="form-text text-muted">Minimum 6 characters</small>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Register</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-center text-white mt-3" style="opacity:0.8; font-size:0.875rem;">
                    <a href="../index.html" class="text-white"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../backend/auth/forgot_password.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                            <small class="form-text text-muted">Enter your email to receive password reset instructions</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>