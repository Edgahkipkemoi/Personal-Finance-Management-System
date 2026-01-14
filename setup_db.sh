#!/bin/bash

echo "=== Personal Finance Management System - Database Setup ==="
echo ""

# Create database and tables
echo "Creating database and tables..."
sudo mysql <<EOF
-- Create database
CREATE DATABASE IF NOT EXISTS personal_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE personal_finance;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NO