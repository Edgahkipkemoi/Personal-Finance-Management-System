<?php
// Personal Finance Management System Helper Functions
// Created by: Edgah Kipkemoi (22/06846)

require_once 'config/config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    return date($format, strtotime($date));
}

/**
 * Calculate percentage
 */
function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 1);
}

/**
 * Get month name
 */
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$month] ?? '';
}

/**
 * Get budget status class for Bootstrap
 */
function getBudgetStatusClass($spent, $budget) {
    if ($budget == 0) return 'bg-secondary';
    
    $percentage = ($spent / $budget) * 100;
    
    if ($percentage >= 100) return 'bg-danger';
    if ($percentage >= 80) return 'bg-warning';
    return 'bg-success';
}

/**
 * Generate pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Validate expense data
 */
function validateExpense($amount, $description, $date, $categoryId) {
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Amount must be a positive number';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if (empty($date) || !strtotime($date)) {
        $errors[] = 'Valid date is required';
    }
    
    if (empty($categoryId) || !is_numeric($categoryId)) {
        $errors[] = 'Category is required';
    }
    
    return $errors;
}

/**
 * Validate budget data
 */
function validateBudget($amount, $month, $year) {
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Budget amount must be a positive number';
    }
    
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        $errors[] = 'Valid month is required';
    }
    
    if (empty($year) || !is_numeric($year) || $year < 2020 || $year > 2030) {
        $errors[] = 'Valid year is required';
    }
    
    return $errors;
}

/**
 * Get expense statistics
 */
function getExpenseStats($db, $userId, $month = null, $year = null) {
    $month = $month ?? date('m');
    $year = $year ?? date('Y');
    
    $query = "SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(AVG(amount), 0) as avg_amount,
                COALESCE(MAX(amount), 0) as max_amount,
                COALESCE(MIN(amount), 0) as min_amount
              FROM expenses 
              WHERE user_id = :user_id 
              AND MONTH(expense_date) = :month 
              AND YEAR(expense_date) = :year";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get category breakdown
 */
function getCategoryBreakdown($db, $userId, $month = null, $year = null) {
    $month = $month ?? date('m');
    $year = $year ?? date('Y');
    
    $query = "SELECT c.category_name, 
                     COALESCE(SUM(e.amount), 0) as total_amount,
                     COUNT(e.expense_id) as transaction_count
              FROM categories c
              LEFT JOIN expenses e ON c.category_id = e.category_id 
              AND e.user_id = :user_id 
              AND MONTH(e.expense_date) = :month 
              AND YEAR(e.expense_date) = :year
              WHERE c.user_id = :user_id OR c.user_id IS NULL
              GROUP BY c.category_id, c.category_name
              HAVING total_amount > 0
              ORDER BY total_amount DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if user is over budget
 */
function isOverBudget($db, $userId, $month = null, $year = null) {
    $month = $month ?? date('m');
    $year = $year ?? date('Y');
    
    // Get budget
    $budgetQuery = "SELECT amount FROM budgets WHERE user_id = :user_id AND month = :month AND year = :year";
    $budgetStmt = $db->prepare($budgetQuery);
    $budgetStmt->bindParam(':user_id', $userId);
    $budgetStmt->bindParam(':month', $month);
    $budgetStmt->bindParam(':year', $year);
    $budgetStmt->execute();
    $budget = $budgetStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$budget) return false;
    
    // Get total expenses
    $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                     WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year";
    $expenseStmt = $db->prepare($expenseQuery);
    $expenseStmt->bindParam(':user_id', $userId);
    $expenseStmt->bindParam(':month', $month);
    $expenseStmt->bindParam(':year', $year);
    $expenseStmt->execute();
    $totalExpenses = $expenseStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $totalExpenses > $budget['amount'];
}

/**
 * Send notification (placeholder for future email/SMS functionality)
 */
function sendNotification($userId, $message, $type = 'info') {
    // This would integrate with email/SMS service in the future
    // For now, we'll just log it
    logError("Notification for user $userId: $message (Type: $type)");
}

/**
 * Backup database (basic implementation)
 */
function backupDatabase($db) {
    try {
        $backupFile = LOG_PATH . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // This is a basic implementation - in production, use mysqldump
        $tables = ['users', 'categories', 'expenses', 'budgets'];
        $backup = '';
        
        foreach ($tables as $table) {
            $query = "SELECT * FROM $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $backup .= "-- Table: $table\n";
            foreach ($rows as $row) {
                $backup .= "INSERT INTO $table VALUES ('" . implode("','", $row) . "');\n";
            }
            $backup .= "\n";
        }
        
        file_put_contents($backupFile, $backup);
        return $backupFile;
    } catch (Exception $e) {
        logError("Backup failed: " . $e->getMessage());
        return false;
    }
}
?>