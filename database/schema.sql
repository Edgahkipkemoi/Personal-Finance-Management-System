-- Personal Finance Management System Database Schema
-- Created by: Edgah Kipkemoi (22/06846)

CREATE DATABASE IF NOT EXISTS personal_finance;
USE personal_finance;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Expenses table
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

-- Budgets table
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

-- Password resets table
CREATE TABLE password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);

-- Insert default categories
INSERT INTO categories (category_name, user_id) VALUES 
('Food & Dining', NULL),
('Transportation', NULL),
('Shopping', NULL),
('Entertainment', NULL),
('Bills & Utilities', NULL),
('Healthcare', NULL),
('Education', NULL),
('Other', NULL);

-- Savings goals table
CREATE TABLE IF NOT EXISTS savings_goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deadline DATE NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Goal contributions table
CREATE TABLE IF NOT EXISTS goal_contributions (
    contribution_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    contribution_date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES savings_goals(goal_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- M-Pesa STK Push payment requests
CREATE TABLE IF NOT EXISTS mpesa_payments (
    payment_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    goal_id           INT NULL,
    phone             VARCHAR(20) NOT NULL,
    amount            DECIMAL(10,2) NOT NULL,
    checkout_request_id VARCHAR(100) NULL,
    merchant_request_id VARCHAR(100) NULL,
    mpesa_receipt     VARCHAR(50) NULL,
    status            ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
    result_desc       VARCHAR(255) NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES savings_goals(goal_id) ON DELETE SET NULL
);

-- M-Pesa SMS-imported transactions
CREATE TABLE IF NOT EXISTS mpesa_transactions (
    transaction_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    mpesa_code        VARCHAR(20) NOT NULL,
    transaction_type  ENUM('sent','received','paybill','till','airtime','withdrawal') NOT NULL,
    amount            DECIMAL(10,2) NOT NULL,
    counterparty      VARCHAR(150) NULL COMMENT 'Person/Business name',
    transaction_date  DATETIME NOT NULL,
    balance_after     DECIMAL(10,2) NULL,
    raw_sms           TEXT NULL,
    expense_id        INT NULL COMMENT 'Linked expense if auto-imported',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mpesa_code (user_id, mpesa_code),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (expense_id) REFERENCES expenses(expense_id) ON DELETE SET NULL
);