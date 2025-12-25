<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->connect();
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = $_POST['category_name'];
        
        $query = "INSERT INTO categories (category_name, user_id) VALUES (:category_name, :user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':category_name', $category_name);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Category added successfully!';
        } else {
            $_SESSION['error'] = 'Failed to add category';
        }
        header('Location: categories.php');
        exit();
    }
    
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        
        // Check if category has expenses
        $check_query = "SELECT COUNT(*) as count FROM expenses WHERE category_id = :category_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':category_id', $category_id);
        $check_stmt->execute();
        $expense_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($expense_count > 0) {
            $_SESSION['error'] = 'Cannot delete category with existing expenses';
        } else {
            $query = "DELETE FROM categories WHERE category_id = :category_id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Category deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete category';
            }
        }
        header('Location: categories.php');
        exit();
    }
}

// Get user categories with expense counts
$categories_query = "SELECT c.*, COUNT(e.expense_id) as expense_count, COALESCE(SUM(e.amount), 0) as total_spent
                     FROM categories c
                     LEFT JOIN expenses e ON c.category_id = e.category_id
                     WHERE c.user_id = :user_id
                     GROUP BY c.category_id, c.category_name
                     ORDER BY c.category_name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->bindParam(':user_id', $user_id);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Personal Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" required>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Categories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-muted">No custom categories yet. Add your first category!</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category Name</th>
                                            <th>Expenses</th>
                                            <th>Total Spent</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                            <td><?php echo $category['expense_count']; ?></td>
                                            <td>KSh <?php echo number_format($category['total_spent'], 2); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                    <button type="submit" name="delete_category" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Are you sure? This will only work if no expenses use this category.')"
                                                            <?php echo $category['expense_count'] > 0 ? 'disabled title="Cannot delete category with expenses"' : ''; ?>>
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
</body>
</html>