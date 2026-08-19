<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgets - Personal Finance Manager</title>
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
                    <li class="nav-item"><a class="nav-link text-white" href="expenses.html"><i class="fas fa-receipt me-1"></i>Expenses</a></li>
                    <li class="nav-item"><a class="nav-link text-white fw-semibold active" href="budgets.php"><i class="fas fa-chart-pie me-1"></i>Budgets</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="goals.html"><i class="fas fa-bullseye me-1"></i>Goals</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="reports.html"><i class="fas fa-chart-line me-1"></i>Reports</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="mpesa.html"><i class="fas fa-mobile-alt me-1"></i>M-Pesa</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="categories.php"><i class="fas fa-tags me-1"></i>Categories</a></li>
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
            <!-- Set Budget Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Set Monthly Budget</h5>
                    </div>
                    <div class="card-body">
                        <form action="../backend/budgets/set.php" method="POST" id="budget-form">
                            <div class="mb-3">
                                <label class="form-label">Budget Amount (KSh)</label>
                                <input type="number" name="amount" step="0.01" class="form-control" id="budget-amount" required>
                                <div class="form-text" id="budget-info"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-control" id="budget-month" required>
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Year</label>
                                <select name="year" class="form-control" id="budget-year" required>
                                    <?php
                                    $cy = (int)date('Y');
                                    for ($y = $cy - 2; $y <= $cy + 3; $y++) {
                                        $sel = ($y === $cy) ? ' selected' : '';
                                        echo "<option value=\"$y\"$sel>$y</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="submit-btn">Set Budget</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Budget Overview -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Budget Overview</h5>
                    </div>
                    <div class="card-body" id="budgets-list">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading budgets...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let budgetsData = [];
        
        async function loadUserInfo() {
            try {
                const response = await fetch('../backend/api/user.php');
                const data = await response.json();
                
                if (!data.authenticated) {
                    window.location.href = 'login.php';
                    return;
                }
                
                document.getElementById('user-dropdown').innerHTML = `<i class="fas fa-user-circle me-1"></i>${data.user_name}`;
            } catch (error) {
                console.error('Error loading user info:', error);
            }
        }

        async function loadBudgets() {
            try {
                const response = await fetch('../backend/api/budgets.php');
                const data = await response.json();
                
                if (data.error && data.error === 'Not authenticated') {
                    window.location.href = 'login.php';
                    return;
                }
                
                budgetsData = Array.isArray(data) ? data : [];
                const container = document.getElementById('budgets-list');
                
                if (budgetsData.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-piggy-bank fa-4x mb-3"></i>
                            <h4>No budgets set yet</h4>
                            <p>Create your first budget to start tracking your spending limits.</p>
                            <p class="small">Use the form on the left to set a monthly budget and monitor your expenses.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                budgetsData.forEach(budget => {
                    const statusClass = budget.status === 'success' ? 'bg-success' : 
                                       budget.status === 'warning' ? 'bg-warning' : 'bg-danger';
                    
                    html += `<div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <h6>${budget.month_name}</h6>
                                </div>
                                <div class="col-md-9">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Budget: KSh ${budget.amount}</span>
                                        <span>Spent: KSh ${budget.spent}</span>
                                    </div>
                                    <div class="progress mb-1">
                                        <div class="progress-bar ${statusClass}" style="width: ${Math.min(budget.percentage, 100)}%" 
                                             role="progressbar" aria-valuenow="${budget.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">${budget.percentage}% used</small>
                                        <small class="text-muted">Remaining: KSh ${budget.remaining}${parseFloat(budget.remaining) < 0 ? 
                                            ` <span class="text-danger">(Over by KSh ${Math.abs(parseFloat(budget.remaining)).toFixed(2)})</span>` : 
                                            ''}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                
                container.innerHTML = html;
                
            } catch (error) {
                console.error('Error loading budgets:', error);
                document.getElementById('budgets-list').innerHTML = `
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Unable to load budgets</strong><br>
                        Please check your connection and try again.
                    </div>
                `;
            }
        }
        
        function checkExistingBudget() {
            const month = parseInt(document.getElementById('budget-month').value);
            const year = parseInt(document.getElementById('budget-year').value);
            const amountInput = document.getElementById('budget-amount');
            const budgetInfo = document.getElementById('budget-info');
            const submitBtn = document.getElementById('submit-btn');
            
            // Find existing budget for selected month/year
            const existing = budgetsData.find(b => parseInt(b.month) === month && parseInt(b.year) === year);
            
            if (existing) {
                const amount = parseFloat(amountInput.value) || 0;
                const existingAmount = parseFloat(existing.amount.replace(/,/g, ''));
                const newTotal = existingAmount + amount;
                
                if (amount > 0) {
                    budgetInfo.innerHTML = `<span class="text-info"><i class="fas fa-info-circle"></i> This will ADD to your existing budget of KSh ${existing.amount}. New total: <strong>KSh ${newTotal.toLocaleString()}</strong></span>`;
                } else {
                    budgetInfo.innerHTML = `<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> A budget of KSh ${existing.amount} already exists for this month.</span>`;
                }
                submitBtn.textContent = 'Add to Budget';
            } else {
                budgetInfo.innerHTML = '';
                submitBtn.textContent = 'Set Budget';
            }
        }
        
        // Set current month and year as default
        document.addEventListener('DOMContentLoaded', function() {
            const currentMonth = new Date().getMonth() + 1;
            const currentYear = new Date().getFullYear();
            
            document.querySelector('select[name="month"]').value = currentMonth;
            document.querySelector('select[name="year"]').value = currentYear;
            
            loadUserInfo();
            loadBudgets();
            
            // Add event listeners for budget checking
            document.getElementById('budget-month').addEventListener('change', checkExistingBudget);
            document.getElementById('budget-year').addEventListener('change', checkExistingBudget);
            document.getElementById('budget-amount').addEventListener('input', checkExistingBudget);
        });
    </script>
</body>
</html>