<?php
// Personal Finance Management System Installer
// Created by: Edgah Kipkemoi (22/06846)

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('System is already installed. Delete config/installed.lock to reinstall.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 2) {
        // Database configuration
        $host = $_POST['host'] ?? 'localhost';
        $dbname = $_POST['dbname'] ?? 'personal_finance';
        $username = $_POST['username'] ?? 'root';
        $password = $_POST['password'] ?? '';
        
        try {
            // Test database connection
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            $pdo->exec("USE `$dbname`");
            
            // Read and execute schema
            $schema = file_get_contents('database/schema.sql');
            $statements = explode(';', $schema);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // Update database config
            $configContent = "<?php
class Database {
    private \$host = '$host';
    private \$db_name = '$dbname';
    private \$username = '$username';
    private \$password = '$password';
    private \$conn;

    public function connect() {
        \$this->conn = null;
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name,
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$e) {
            echo \"Connection error: \" . \$e->getMessage();
        }
        return \$this->conn;
    }
}
?>";
            
            file_put_contents('config/database.php', $configContent);
            $success[] = 'Database configured successfully!';
            $step = 3;
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if ($step == 3) {
        // Admin user creation
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = 'All fields are required';
        } elseif ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } else {
            try {
                require_once 'config/database.php';
                $database = new Database();
                $db = $database->connect();
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                
                if ($stmt->execute()) {
                    $user_id = $db->lastInsertId();
                    
                    // Create default categories for admin user
                    $categories = ['Food & Dining', 'Transportation', 'Shopping', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Other'];
                    $cat_query = "INSERT INTO categories (category_name, user_id) VALUES (:category_name, :user_id)";
                    $cat_stmt = $db->prepare($cat_query);
                    
                    foreach ($categories as $category) {
                        $cat_stmt->bindParam(':category_name', $category);
                        $cat_stmt->bindParam(':user_id', $user_id);
                        $cat_stmt->execute();
                    }
                    
                    // Create installation lock file
                    file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                    
                    $success[] = 'Installation completed successfully!';
                    $step = 4;
                } else {
                    $errors[] = 'Failed to create admin user';
                }
                
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Personal Finance Manager - Installation</h3>
                        <small>Created by: Edgah Kipkemoi (22/06846)</small>
                    </div>
                    <div class="card-body">
                        <!-- Progress bar -->
                        <div class="progress mb-4">
                            <div class="progress-bar" style="width: <?php echo ($step / 4) * 100; ?>%">
                                Step <?php echo $step; ?> of 4
                            </div>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <ul class="mb-0">
                                    <?php foreach ($success as $msg): ?>
                                        <li><?php echo htmlspecialchars($msg); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($step == 1): ?>
                            <h4>Welcome to Personal Finance Manager</h4>
                            <p>This installer will help you set up your personal finance management system.</p>
                            
                            <h5>System Requirements:</h5>
                            <ul class="list-group mb-4">
                                <li class="list-group-item d-flex justify-content-between">
                                    PHP 7.4+ 
                                    <span class="badge bg-<?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'danger'; ?>">
                                        <?php echo PHP_VERSION; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    MySQL/MariaDB
                                    <span class="badge bg-<?php echo extension_loaded('pdo_mysql') ? 'success' : 'danger'; ?>">
                                        <?php echo extension_loaded('pdo_mysql') ? 'Available' : 'Missing'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    PDO Extension
                                    <span class="badge bg-<?php echo extension_loaded('pdo') ? 'success' : 'danger'; ?>">
                                        <?php echo extension_loaded('pdo') ? 'Available' : 'Missing'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    Config Directory Writable
                                    <span class="badge bg-<?php echo is_writable('config') ? 'success' : 'danger'; ?>">
                                        <?php echo is_writable('config') ? 'Yes' : 'No'; ?>
                                    </span>
                                </li>
                            </ul>
                            
                            <a href="?step=2" class="btn btn-primary">Continue to Database Setup</a>

                        <?php elseif ($step == 2): ?>
                            <h4>Database Configuration</h4>
                            <p>Please provide your database connection details.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Database Host</label>
                                    <input type="text" name="host" class="form-control" value="localhost" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" name="dbname" class="form-control" value="personal_finance" required>
                                    <small class="form-text text-muted">Database will be created if it doesn't exist</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="root" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Test Connection & Create Database</button>
                            </form>

                        <?php elseif ($step == 3): ?>
                            <h4>Create Admin User</h4>
                            <p>Create your administrator account.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">Complete Installation</button>
                            </form>

                        <?php elseif ($step == 4): ?>
                            <div class="text-center">
                                <h4 class="text-success">Installation Complete!</h4>
                                <p>Your Personal Finance Management System has been successfully installed.</p>
                                
                                <div class="alert alert-info">
                                    <strong>Important:</strong> For security reasons, please delete the <code>install.php</code> file.
                                </div>
                                
                                <a href="index.php" class="btn btn-primary btn-lg">Go to Login Page</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>