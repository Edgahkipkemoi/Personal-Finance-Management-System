<?php
// Savings Goals API - Personal Finance Management System
// Handles CRUD operations for savings goals

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->connect();

    switch ($method) {
        case 'GET':
            handleGet($pdo, $user_id);
            break;
        case 'POST':
            handlePost($pdo, $user_id);
            break;
        case 'PUT':
            handlePut($pdo, $user_id);
            break;
        case 'DELETE':
            handleDelete($pdo, $user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($pdo, $user_id) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all goals with progress
            $stmt = $pdo->prepare("
                SELECT 
                    goal_id,
                    goal_name,
                    target_amount,
                    current_amount,
                    deadline,
                    description,
                    created_at,
                    ROUND((current_amount / target_amount) * 100, 2) as progress_percentage,
                    (target_amount - current_amount) as remaining_amount,
                    DATEDIFF(deadline, CURDATE()) as days_remaining,
                    CASE 
                        WHEN current_amount >= target_amount THEN 'Completed'
                        WHEN DATEDIFF(deadline, CURDATE()) < 0 THEN 'Overdue'
                        WHEN DATEDIFF(deadline, CURDATE()) <= 30 THEN 'Urgent'
                        ELSE 'On Track'
                    END as status
                FROM savings_goals 
                WHERE user_id = ? 
                ORDER BY deadline ASC
            ");
            $stmt->execute([$user_id]);
            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format amounts and dates
            foreach ($goals as &$goal) {
                $goal['target_amount'] = number_format($goal['target_amount'], 2);
                $goal['current_amount'] = number_format($goal['current_amount'], 2);
                $goal['remaining_amount'] = number_format($goal['remaining_amount'], 2);
                $goal['deadline'] = date('M j, Y', strtotime($goal['deadline']));
                $goal['created_at'] = date('M j, Y', strtotime($goal['created_at']));
            }
            
            echo json_encode($goals);
            break;
            
        case 'summary':
            // Get goals summary for dashboard
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_goals,
                    COUNT(CASE WHEN current_amount >= target_amount THEN 1 END) as completed_goals,
                    SUM(target_amount) as total_target,
                    SUM(current_amount) as total_saved,
                    ROUND(AVG((current_amount / target_amount) * 100), 2) as avg_progress
                FROM savings_goals 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get urgent goals (deadline within 30 days)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as urgent_goals
                FROM savings_goals 
                WHERE user_id = ? 
                AND DATEDIFF(deadline, CURDATE()) BETWEEN 0 AND 30
                AND current_amount < target_amount
            ");
            $stmt->execute([$user_id]);
            $urgent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $summary['urgent_goals'] = $urgent['urgent_goals'];
            $summary['total_target'] = number_format($summary['total_target'] ?? 0, 2);
            $summary['total_saved'] = number_format($summary['total_saved'] ?? 0, 2);
            
            echo json_encode($summary);
            break;
            
        case 'contributions':
            // Get contributions for a specific goal
            $goal_id = $_GET['goal_id'] ?? null;
            if (!$goal_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Goal ID required']);
                return;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    contribution_id,
                    amount,
                    contribution_date,
                    description,
                    created_at
                FROM goal_contributions 
                WHERE goal_id = ? AND user_id = ?
                ORDER BY contribution_date DESC
            ");
            $stmt->execute([$goal_id, $user_id]);
            $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($contributions as &$contribution) {
                $contribution['amount'] = number_format($contribution['amount'], 2);
                $contribution['contribution_date'] = date('M j, Y', strtotime($contribution['contribution_date']));
                $contribution['created_at'] = date('M j, Y g:i A', strtotime($contribution['created_at']));
            }
            
            echo json_encode($contributions);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create_goal';
    
    switch ($action) {
        case 'create_goal':
            // Validate required fields
            $required = ['goal_name', 'target_amount', 'deadline'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                    return;
                }
            }
            
            // Validate amount
            if (!is_numeric($input['target_amount']) || $input['target_amount'] <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Target amount must be a positive number']);
                return;
            }
            
            // Validate deadline
            if (strtotime($input['deadline']) <= time()) {
                http_response_code(400);
                echo json_encode(['error' => 'Deadline must be in the future']);
                return;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO savings_goals (goal_name, target_amount, deadline, description, user_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['goal_name'],
                $input['target_amount'],
                $input['deadline'],
                $input['description'] ?? '',
                $user_id
            ]);
            
            $goal_id = $pdo->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Savings goal created successfully!',
                'goal_id' => $goal_id
            ]);
            break;
            
        case 'add_contribution':
            // Validate required fields
            $required = ['goal_id', 'amount', 'contribution_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                    return;
                }
            }
            
            // Validate amount
            if (!is_numeric($input['amount']) || $input['amount'] <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Amount must be a positive number']);
                return;
            }
            
            // Verify goal belongs to user
            $stmt = $pdo->prepare("SELECT goal_id FROM savings_goals WHERE goal_id = ? AND user_id = ?");
            $stmt->execute([$input['goal_id'], $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Goal not found']);
                return;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO goal_contributions (goal_id, amount, contribution_date, description, user_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['goal_id'],
                $input['amount'],
                $input['contribution_date'],
                $input['description'] ?? '',
                $user_id
            ]);
            
            // Update goal current_amount manually
            $stmt = $pdo->prepare("
                UPDATE savings_goals 
                SET current_amount = (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM goal_contributions 
                    WHERE goal_id = ?
                )
                WHERE goal_id = ?
            ");
            $stmt->execute([$input['goal_id'], $input['goal_id']]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Contribution added successfully!'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $goal_id = $input['goal_id'] ?? null;
    
    if (!$goal_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Goal ID required']);
        return;
    }
    
    // Verify goal belongs to user
    $stmt = $pdo->prepare("SELECT goal_id FROM savings_goals WHERE goal_id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Goal not found']);
        return;
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [];
    
    $allowed_fields = ['goal_name', 'target_amount', 'deadline', 'description'];
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $params[] = $goal_id;
    $params[] = $user_id;
    
    $sql = "UPDATE savings_goals SET " . implode(', ', $updates) . " WHERE goal_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Goal updated successfully!'
    ]);
}

function handleDelete($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $goal_id = $input['goal_id'] ?? null;
    
    if (!$goal_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Goal ID required']);
        return;
    }
    
    // Verify goal belongs to user
    $stmt = $pdo->prepare("SELECT goal_id FROM savings_goals WHERE goal_id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Goal not found']);
        return;
    }
    
    // Delete goal (contributions will be deleted by CASCADE)
    $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE goal_id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Goal deleted successfully!'
    ]);
}
?>