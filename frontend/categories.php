<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg" style="background:linear-gradient(135deg,#667eea,#764ba2);">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="dashboard.html">
                <i class="fas fa-wallet me-2"></i>Finance Manager
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link text-white" href="dashboard.html"><i class="fas fa-home me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="expenses.php"><i class="fas fa-receipt me-1"></i>Expenses</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="budgets.php"><i class="fas fa-chart-pie me-1"></i>Budgets</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="goals.html"><i class="fas fa-bullseye me-1"></i>Goals</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="reports.html"><i class="fas fa-chart-line me-1"></i>Reports</a></li>
                    <li class="nav-item"><a class="nav-link text-white fw-semibold active" href="categories.php"><i class="fas fa-tags me-1"></i>Categories</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" id="user-dropdown">
                            <i class="fas fa-user-circle me-1"></i>Loading...
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.html"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../backend/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();

        // Server-side auth guard
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf_token = $_SESSION['csrf_token'];

        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        
        <div class="row">
            <!-- Add Category Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form action="../backend/categories/add.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Categories List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Categories</h5>
                    </div>
                    <div class="card-body">
                        <div id="categories-list">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading categories...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Default Categories Info -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6>Default Categories</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            The system includes default categories: Food & Dining, Transportation, Shopping, 
                            Entertainment, Bills & Utilities, Healthcare, Education, and Other. 
                            These cannot be deleted but you can create your own custom categories above.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;

        async function loadUserInfo() {
            try {
                const response = await fetch('../backend/api/user.php');
                const data = await response.json();
                
                if (!data.authenticated) {
                    window.location.href = 'login.php';
                    return;
                }
                
                document.getElementById('user-dropdown').textContent = data.user_name;
            } catch (error) {
                console.error('Error loading user info:', error);
            }
        }

        async function loadCategories() {
            try {
                const response = await fetch('../backend/api/categories.php');
                const data = await response.json();
                
                if (data.error && data.error === 'Not authenticated') {
                    window.location.href = 'login.php';
                    return;
                }
                
                const categories = Array.isArray(data) ? data : [];
                const container = document.getElementById('categories-list');
                
                // Filter out default categories (user_id is null) and only show user's custom categories
                const userCategories = categories.filter(cat => cat.user_specific === true);
                
                if (userCategories.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-tags fa-3x mb-3"></i>
                            <h5>No custom categories yet</h5>
                            <p>Create your first custom category using the form on the left.</p>
                            <p class="small">You can use the default categories or create your own specific ones.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '<div class="table-responsive"><table class="table table-hover"><thead class="table-dark"><tr><th>Category Name</th><th>Actions</th></tr></thead><tbody>';
                
                userCategories.forEach(category => {
                    html += `<tr>
                        <td><span class="badge bg-primary">${category.name}</span></td>
                        <td>
                            <form method="POST" action="../backend/categories/delete.php" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                                <input type="hidden" name="category_id" value="${category.id}">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                container.innerHTML = html;
                
            } catch (error) {
                console.error('Error loading categories:', error);
                document.getElementById('categories-list').innerHTML = `
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Unable to load categories</strong><br>
                        Please check your connection and try again.
                    </div>
                `;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadUserInfo();
            loadCategories();
        });
    </script>
</body>
</html>