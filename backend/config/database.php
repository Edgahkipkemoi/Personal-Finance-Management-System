<?php
// Personal Finance Management System - SQLite Database Configuration
// Created by: Edgah Kipkemoi (22/06846)

class Database {
    private $db_path;
    private $conn;

    public function __construct() {
        // Use absolute path from project root
        $this->db_path = dirname(dirname(__DIR__)) . "/database/personal_finance.db";
    }

    public function connect() {
        $this->conn = null;
        try {
            // Check if database file exists
            if (!file_exists($this->db_path)) {
                throw new Exception("Database file not found at: " . $this->db_path);
            }
            
            // Check if database file is readable
            if (!is_readable($this->db_path)) {
                throw new Exception("Database file not readable at: " . $this->db_path);
            }
            
            $this->conn = new PDO("sqlite:" . $this->db_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys for SQLite
            $this->conn->exec("PRAGMA foreign_keys = ON");
            
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            error_log("Database path: " . $this->db_path);
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
    
    // Debug method to check path
    public function getDbPath() {
        return $this->db_path;
    }
}
?>