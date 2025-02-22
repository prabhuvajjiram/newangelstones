<?php
require_once('includes/db_config.php');

try {
    $conn = new mysqli($host, $user, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$database'");
    
    if ($result->num_rows == 0) {
        // Create database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS $database");
        echo "Database '$database' created successfully!<br>";
    } else {
        echo "Database '$database' already exists.<br>";
    }
    
    // Select the database
    $conn->select_db($database);
    
    echo "MySQL is running and database connection is successful!<br>";
    echo "Server version: " . $conn->server_info . "<br>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$conn->close();
?>
