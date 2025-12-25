<?php
// Local MySQL Setup for Personal Finance Management System
// Created by: Edgah Kipkemoi (22/06846)

echo "🚀 Personal Finance Management System - Local MySQL Setup\n";
echo "========================================================\n\n";

try {
    echo "📡 Step 1: Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✅ MySQL connection successful!\n\n";
    
    echo "🗄️  Step 2: Creating database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS personal_finance");
    $pdo->exec("USE personal_finance");
    echo "✅ Database 'personal_finance' ready!\n\n";
    
    echo "📋 Step 3: Creating tables...\n";
    
    // Create tables directly (cleaner than reading file)
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(50) NOT NULL,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS expenses (
            expense_id INT AUTO_INCREMENT PRIMARY KEY,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            expense_date DATE NOT NULL,
            category_id INT,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS budgets (
            budget_id INT AUTO_INCREMENT PRIMARY KEY,
            amount DECIMAL(10,2) NOT NULL,
            month INT NOT NULL,
            year INT NOT NULL,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_budget (user_id, month, year)
        )",
        
        "CREATE TABLE IF NOT EXISTS password_resets (
            reset_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id)
        )"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    echo "✅ All tables created!\n\n";
    
    echo "🎯 Step 4: Adding default categories...\n";
    $categories = ['Food & Dining', 'Transportation', 'Shopping', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Other'];
    
    foreach ($categories as $category) {
        $pdo->exec("INSERT IGNORE INTO categories (category_name, user_id) VALUES ('$category', NULL)");
    }
    echo "✅ Default categories added!\n\n";
    
    echo "🎊 SETUP COMPLETE!\n";
    echo "==================\n";
    echo "✅ Database: personal_finance\n";
    echo "✅ Host: localhost\n";
    echo "✅ Username: root\n";
    echo "✅ Password: (empty)\n\n";
    
    echo "🚀 Next Steps:\n";
    echo "1. Go to: http://localhost:8000/frontend/login.html\n";
    echo "2. Register your account\n";
    echo "3. Start tracking expenses!\n\n";
    
} catch (PDOException $e) {
    echo "❌ Setup Failed: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "🔧 Fix MySQL Access:\n";
        echo "Run these commands:\n\n";
        echo "sudo mysql\n";
        echo "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';\n";
        echo "FLUSH PRIVILEGES;\n";
        echo "EXIT;\n\n";
        echo "Then run this setup again: php setup_local_mysql.php\n";
    } else {
        echo "💡 Start MySQL first:\n";
        echo "sudo systemctl start mysql\n";
        echo "sudo systemctl enable mysql\n\n";
    }
}
?>