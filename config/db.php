<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../db_errors.log');

try {
    // First connect without selecting a database
    $conn = new mysqli('localhost', 'root', '');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS wallet_tally";
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db('wallet_tally');
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    // Verify the connection
    if ($conn->ping()) {
        error_log("Database connection successful");
    } else {
        throw new Exception("Database connection verification failed");
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    die("Database connection failed. Please check the error logs.");
}
?> 