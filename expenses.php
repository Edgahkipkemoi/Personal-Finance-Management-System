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
    if (isset($_POST['add_expense'])) {
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $expense_date = $_POST['expense_date'];
        $category_id = $_POST['category_id'];
        
        $query = "INSERT INTO expenses (amount, description, expense_date, category_id, user_id) 
                  VALUES (:amount, :description, :expense_date, :category_id, :user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':expense_date', $expense_date);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Expense added successfully!';
        } else {
            $_SESSION['error'] = 'Failed to add expense';
        }
        header('Location: expenses.php');
        exit();
    }
    
    if (isset($_POST['delete_expense'])) {
        $expense_id = $_POST['expense_id'];
        $query = "DELETE FROM expenses WHERE expense_id = :expense_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':expense_id', $expense_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Expense deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete expense';
        }
        header('Location: expenses.php');
        exit();
    }
}

// Get categories
$cat_query = "SELECT * FROM categories WHERE user_id = :user_id OR user_id IS NULL ORDER BY category_name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->bindParam(':user_id', $user_id);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expenses with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$expenses_query = "SELECT e.*, c.category_name FROM expenses e 
                   LEFT JOIN categories c ON e.category_id = c.category_id 
                   WHERE e.user_id = :user_id 
                   ORDER BY e.expense_date DESC, e.created_at DESC 
                   LIMIT :limit OFFSET :offset";
$expenses_stmt = $db->prepare($expenses_query);
$expenses_stmt->bindParam(':user_id', $user_id);
$expenses_stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$expenses_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$expenses_stmt->execute();
$expenses = $expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM expenses WHERE user_id = :user_id";
$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(':user_id', $user_id);
$count_stmt->execute();
$total_expenses = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_expenses / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Personal Finance Manager</title>
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
                        <h5>Add New Expense</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Amount (KSh)</label>
                                <input type="number" name="amount" step="0.01" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_expense" class="btn btn-primary w-100">Add Expense</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Expense History</h5>
                        <a href="export.php" class="btn btn-success btn-sm">Export CSV</a>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>KSh <?php echo number_format($expense['amount'], 2); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="expense_id" value="<?php echo $expense['expense_id']; ?>">
                                            <button type="submit" name="delete_expense" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>