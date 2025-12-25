# Personal Finance Management System - Technical Documentation

**Created by:** Edgah Kipkemoi (22/06846)  
**Course:** BSD 3106 - Bachelor of Software Development  
**Supervisor:** Griffin Kenga  
**Date:** 6/12/2025

## Table of Contents
1. [System Architecture](#system-architecture)
2. [Database Design](#database-design)
3. [File Structure](#file-structure)
4. [Security Implementation](#security-implementation)
5. [API Documentation](#api-documentation)
6. [Development Guidelines](#development-guidelines)
7. [Testing Strategy](#testing-strategy)
8. [Deployment Guide](#deployment-guide)

## System Architecture

### Three-Tier Architecture
The system follows a three-tier architecture pattern:

#### 1. Presentation Layer (Frontend)
- **Technologies:** HTML5, CSS3, JavaScript, Bootstrap 5.3
- **Components:** User interface, forms, charts, responsive design
- **Files:** `*.php` (view logic), `assets/css/`, `assets/js/`

#### 2. Application Layer (Backend)
- **Technology:** PHP 7.4+
- **Components:** Business logic, authentication, data validation
- **Files:** `auth/`, `includes/`, `config/`

#### 3. Data Layer (Database)
- **Technology:** MySQL 5.7+
- **Components:** Data storage, relationships, constraints
- **Files:** `database/schema.sql`

### Design Patterns Used

#### MVC Pattern (Simplified)
- **Model:** Database classes and data access
- **View:** HTML templates with embedded PHP
- **Controller:** PHP scripts handling requests

#### Singleton Pattern
- Database connection class ensures single instance

#### Factory Pattern
- Database factory for connection management

## Database Design

### Entity Relationship Diagram

```
Users (1) -----> (M) Categories
  |                    |
  |                    |
  v                    v
Expenses (M) -----> (1) Categories
  |
  |
Users (1) -----> (M) Budgets
```

### Table Specifications

#### Users Table
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Categories Table
```sql
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### Expenses Table
```sql
CREATE TABLE expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    category_id INT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### Budgets Table
```sql
CREATE TABLE budgets (
    budget_id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_budget (user_id, month, year)
);
```

### Database Relationships
- **One-to-Many:** Users → Expenses, Users → Categories, Users → Budgets
- **Many-to-One:** Expenses → Categories
- **Constraints:** Foreign keys with CASCADE/SET NULL options

### Indexing Strategy
- Primary keys: Auto-indexed
- Foreign keys: Indexed for join performance
- Email field: Unique index for login queries
- Date fields: Indexed for report queries

## File Structure

```
personal-finance-system/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── config/
│   ├── config.php
│   ├── database.php
│   └── installed.lock
├── database/
│   └── schema.sql
├── documentation/
│   ├── TECHNICAL_DOCUMENTATION.md
│   └── USER_MANUAL.md
├── includes/
│   ├── functions.php
│   └── navbar.php
├── logs/
│   └── (log files)
├── uploads/
│   └── (uploaded files)
├── budgets.php
├── categories.php
├── dashboard.php
├── expenses.php
├── export.php
├── index.php
├── install.php
├── README.md
└── reports.php
```

### File Descriptions

#### Core Files
- `index.php`: Login/registration page
- `dashboard.php`: Main dashboard with overview
- `expenses.php`: Expense management
- `budgets.php`: Budget management
- `reports.php`: Analytics and reporting
- `categories.php`: Category management
- `export.php`: Data export functionality

#### Configuration Files
- `config/database.php`: Database connection class
- `config/config.php`: Application configuration
- `includes/functions.php`: Helper functions

#### Authentication
- `auth/login.php`: User authentication
- `auth/register.php`: User registration
- `auth/logout.php`: Session termination

## Security Implementation

### Authentication & Authorization
```php
// Session-based authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
```

### Password Security
```php
// Password hashing
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Password verification
if (password_verify($password, $user['password'])) {
    // Login successful
}
```

### SQL Injection Prevention
```php
// Prepared statements
$query = "SELECT * FROM expenses WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
```

### XSS Prevention
```php
// Output escaping
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### CSRF Protection (Future Enhancement)
```php
// CSRF token generation
function generateCSRFToken() {
    return bin2hex(random_bytes(32));
}
```

### Data Validation
```php
// Input validation
function validateExpense($amount, $description, $date, $categoryId) {
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Amount must be a positive number';
    }
    
    return $errors;
}
```

## API Documentation

### Internal Functions

#### Database Operations
```php
// Get expense statistics
getExpenseStats($db, $userId, $month, $year)

// Get category breakdown
getCategoryBreakdown($db, $userId, $month, $year)

// Check budget status
isOverBudget($db, $userId, $month, $year)
```

#### Utility Functions
```php
// Format currency
formatCurrency($amount)

// Format dates
formatDate($date, $format)

// Calculate percentage
calculatePercentage($part, $total)
```

### Data Flow

#### Expense Creation Flow
1. User submits expense form
2. Server validates input data
3. Data inserted into database
4. User redirected with success message
5. Dashboard updated with new data

#### Report Generation Flow
1. User selects report parameters
2. Server queries database with filters
3. Data processed and aggregated
4. Charts and tables generated
5. Results displayed to user

## Development Guidelines

### Coding Standards

#### PHP Standards
- Follow PSR-12 coding standard
- Use meaningful variable names
- Comment complex logic
- Handle errors gracefully

```php
// Good example
function calculateMonthlyTotal($userId, $month, $year) {
    try {
        // Implementation
    } catch (Exception $e) {
        logError($e->getMessage());
        return 0;
    }
}
```

#### HTML/CSS Standards
- Use semantic HTML5 elements
- Follow Bootstrap conventions
- Responsive design principles
- Accessibility considerations

#### JavaScript Standards
- Use modern ES6+ features
- Handle errors gracefully
- Optimize for performance
- Follow naming conventions

### Version Control
- Use Git for version control
- Meaningful commit messages
- Feature branch workflow
- Regular backups

### Documentation
- Comment complex functions
- Update documentation with changes
- Include examples in comments
- Maintain changelog

## Testing Strategy

### Unit Testing
```php
// Example test case
function testExpenseValidation() {
    $errors = validateExpense(-100, '', '', '');
    assert(count($errors) > 0, 'Should return validation errors');
}
```

### Integration Testing
- Test database connections
- Test form submissions
- Test user authentication
- Test report generation

### System Testing
- Full workflow testing
- Cross-browser compatibility
- Performance testing
- Security testing

### User Acceptance Testing
- Test with real users
- Validate requirements
- Usability testing
- Feedback incorporation

## Deployment Guide

### Production Environment Setup

#### Server Requirements
- Apache/Nginx web server
- PHP 7.4+ with required extensions
- MySQL 5.7+ database server
- SSL certificate (recommended)

#### Configuration Steps
1. Upload files to web server
2. Set proper file permissions
3. Configure database connection
4. Run installation script
5. Remove installation files
6. Configure SSL (if available)

#### Security Hardening
```apache
# .htaccess example
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>
```

### Backup Strategy
- Daily database backups
- Weekly full system backups
- Offsite backup storage
- Backup restoration testing

### Monitoring
- Error log monitoring
- Performance monitoring
- Security monitoring
- User activity tracking

### Maintenance
- Regular security updates
- Database optimization
- Log file rotation
- Performance tuning

## Performance Optimization

### Database Optimization
- Proper indexing strategy
- Query optimization
- Connection pooling
- Regular maintenance

### Frontend Optimization
- CSS/JS minification
- Image optimization
- CDN usage for libraries
- Caching strategies

### Server Optimization
- PHP opcode caching
- Database query caching
- Gzip compression
- HTTP/2 support

## Future Enhancements

### Planned Features
1. Mobile application
2. API for third-party integrations
3. Advanced reporting
4. Multi-currency support
5. Data import functionality
6. Email notifications
7. Recurring expenses
8. Goal tracking

### Technical Improvements
1. RESTful API implementation
2. Modern PHP framework migration
3. Frontend framework integration
4. Automated testing suite
5. CI/CD pipeline
6. Docker containerization
7. Cloud deployment options
8. Real-time notifications

---

**End of Technical Documentation**

This documentation should be updated as the system evolves and new features are added.