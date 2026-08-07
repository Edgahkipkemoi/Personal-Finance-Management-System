#!/bin/bash

echo "=== Personal Finance Management System - Database Setup ==="
echo ""

# ---------------------------------------------------------------------------
# 1. Load credentials from .env if it exists
# ---------------------------------------------------------------------------
ENV_FILE="$(dirname "$0")/.env"
if [ -f "$ENV_FILE" ]; then
    echo "Loading credentials from .env..."
    # shellcheck disable=SC1090
    export $(grep -v '^#' "$ENV_FILE" | xargs)
else
    echo "WARNING: .env file not found. Copy .env.example to .env and fill in your credentials."
    exit 1
fi

DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-personal_finance}"
DB_USER="${DB_USERNAME:-pfm_user}"
DB_PASS="${DB_PASSWORD}"

# ---------------------------------------------------------------------------
# 2. Create database user and schema
# ---------------------------------------------------------------------------
echo "Creating database user and tables..."
sudo mysql <<EOF
-- Create database
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create application user (skip if already exists)
CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'${DB_HOST}';
FLUSH PRIVILEGES;

USE ${DB_NAME};

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    user_id       INT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    expense_id   INT AUTO_INCREMENT PRIMARY KEY,
    amount       DECIMAL(10,2) NOT NULL,
    description  TEXT,
    expense_date DATE NOT NULL,
    category_id  INT,
    user_id      INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE
);

-- Budgets table
CREATE TABLE IF NOT EXISTS budgets (
    budget_id  INT AUTO_INCREMENT PRIMARY KEY,
    amount     DECIMAL(10,2) NOT NULL,
    month      INT NOT NULL,
    year       INT NOT NULL,
    user_id    INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_budget (user_id, month, year)
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);

-- Savings goals table
CREATE TABLE IF NOT EXISTS savings_goals (
    goal_id        INT AUTO_INCREMENT PRIMARY KEY,
    goal_name      VARCHAR(100) NOT NULL,
    target_amount  DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deadline       DATE NOT NULL,
    description    TEXT,
    user_id        INT NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Goal contributions table
CREATE TABLE IF NOT EXISTS goal_contributions (
    contribution_id   INT AUTO_INCREMENT PRIMARY KEY,
    goal_id           INT NOT NULL,
    amount            DECIMAL(10,2) NOT NULL,
    contribution_date DATE NOT NULL,
    description       TEXT,
    user_id           INT NOT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id)  REFERENCES savings_goals(goal_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(user_id) ON DELETE CASCADE
);

-- Default system categories (user_id NULL = available to all users)
INSERT IGNORE INTO categories (category_name, user_id) VALUES
    ('Food & Dining',    NULL),
    ('Transportation',   NULL),
    ('Shopping',         NULL),
    ('Entertainment',    NULL),
    ('Bills & Utilities',NULL),
    ('Healthcare',       NULL),
    ('Education',        NULL),
    ('Other',            NULL);
EOF

echo ""
echo "=== Setup complete! ==="
echo ""
echo "Tables created: users, categories, expenses, budgets, password_resets, savings_goals, goal_contributions"
echo ""
echo "Start the server with:"
echo "  php -S localhost:8000"
echo ""
echo "Then open: http://localhost:8000/"
