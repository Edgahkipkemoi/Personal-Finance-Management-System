<?php
// Fresh Setup - Personal Finance Management System
// Using SQLite (No MySQL required!)
// Created by: Edgah Kipkemoi (22/06846)

echo "🚀 Personal Finance Management System - Fresh Setup\n";
echo "===================================================\n\n";

try {
    echo "📁 Step 1: Creating database directory...\n";
    if (!file_exists('database')) {
        mkdir('database', 0755, true);
    }
    echo "✅ Database directory ready!\n\n";
    
    echo "🗄️  Step 2: Creating SQLite database...\n";
    $db_path = 'database/personal_finance.db';
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ SQLite database created!\n\n";
    
    echo "📋 Step 3: Creating tables...\n";
    
    // Create all tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS categories (
            category_id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_name TEXT NOT NULL,
            user_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS expenses (
            expense_id INTEGER PRIMARY KEY AUTOINCREMENT,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            expense_date DATE NOT NULL,
            category_id INTEGER,
            user_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS budgets (
            budget_id INTEGER PRIMARY KEY AUTOINCREMENT,
            amount DECIMAL(10,2) NOT NULL,
            month INTEGER NOT NULL,
            year INTEGER NOT NULL,
            user_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE(user_id, month, year)
        )",
        
        "CREATE TABLE IF NOT EXISTS password_resets (
            reset_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    echo "✅ All tables created!\n\n";
    
    echo "🎯 Step 4: Adding default categories...\n";
    $categories = [
        'Food & Dining', 'Transportation', 'Shopping', 'Entertainment', 
        'Bills & Utilities', 'Healthcare', 'Education', 'Other'
    ];
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO categories (category_name, user_id) VALUES (?, NULL)");
    foreach ($categories as $category) {
        $stmt->execute([$category]);
    }
    echo "✅ Default categories added!\n\n";
    
    echo "⚙️  Step 5: Updating database configuration...\n";
    $config = '<?php
// Personal Finance Management System - SQLite Database Configuration
// Created by: Edgah Kipkemoi (22/06846)

class Database {
    private $db_path;
    private $conn;

    public function __construct() {
        $this->db_path = __DIR__ . "/../../database/personal_finance.db";
    }

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO("sqlite:" . $this->db_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys for SQLite
            $this->conn->exec("PRAGMA foreign_keys = ON");
            
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
        return $this->conn;
    }
}
?>';
    
    file_put_contents('backend/config/database.php', $config);
    echo "✅ Database configuration updated!\n\n";
    
    echo "🎊 SETUP COMPLETE!\n";
    echo "==================\n";
    echo "✅ Database: SQLite (No MySQL needed!)\n";
    echo "✅ Location: database/personal_finance.db\n";
    echo "✅ No passwords required!\n";
    echo "✅ Ready to use!\n\n";
    
    echo "🚀 Next Steps:\n";
    echo "1. Go to: http://localhost:8000/frontend/login.html\n";
    echo "2. Register your account\n";
    echo "3. Start tracking expenses!\n\n";
    
    echo "💡 Benefits of SQLite:\n";
    echo "• No MySQL installation needed\n";
    echo "• No password configuration\n";
    echo "• File-based database\n";
    echo "• Perfect for development and small apps\n";
    echo "• Easy to backup (just copy the .db file)\n\n";
    
} catch (Exception $e) {
    echo "❌ Setup Failed: " . $e->getMessage() . "\n\n";
    echo "💡 Make sure PHP SQLite extension is installed:\n";
    echo "sudo apt install php-sqlite3\n\n";
}
?>