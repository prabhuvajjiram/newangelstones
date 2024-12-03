<?php
// Order of SQL files to combine
$files = [
    'backup_angelstones_quotes_new.sql',    // Main database structure and data
    'create_oauth_admin.sql',               // OAuth related tables
    'create_settings_tables.sql',           // Settings tables
    'update_admin_role.sql',                // Update admin roles
    'update_roles.sql',                     // Update user roles
    'update_super_admin.sql'                // Set up super admin
];

$output = "-- Combined setup script for Angel Stones Database\n";
$output .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET NAMES utf8mb4;\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Process consolidated_setup.sql separately to extract table creation and data
if (file_exists(__DIR__ . '/consolidated_setup.sql')) {
    $consolidated = file_get_contents(__DIR__ . '/consolidated_setup.sql');
    
    // Remove database creation commands
    $lines = explode("\n", $consolidated);
    $filtered_lines = array_filter($lines, function($line) {
        $line = trim($line);
        return !empty($line) && 
               stripos($line, 'DROP DATABASE') === false &&
               stripos($line, 'CREATE DATABASE') === false &&
               stripos($line, 'USE') === false;
    });
    
    $output .= "\n-- Including table creation from consolidated_setup.sql\n";
    $output .= implode("\n", $filtered_lines);
    $output .= "\n\n";
}

// Create users table first
$output .= "-- Create users table\n";
$output .= "CREATE TABLE IF NOT EXISTS users (\n";
$output .= "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
$output .= "    username VARCHAR(50) NOT NULL UNIQUE,\n";
$output .= "    password VARCHAR(255) NOT NULL,\n";
$output .= "    email VARCHAR(100) NOT NULL UNIQUE,\n";
$output .= "    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',\n";
$output .= "    active BOOLEAN NOT NULL DEFAULT TRUE,\n";
$output .= "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
$output .= "    last_login TIMESTAMP NULL\n";
$output .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

// Create customers table
$output .= "-- Create customers table\n";
$output .= "CREATE TABLE IF NOT EXISTS customers (\n";
$output .= "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
$output .= "    name VARCHAR(100) NOT NULL,\n";
$output .= "    email VARCHAR(100) NOT NULL UNIQUE,\n";
$output .= "    phone VARCHAR(20),\n";
$output .= "    address TEXT,\n";
$output .= "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n";
$output .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

// Create quotes table
$output .= "-- Create quotes table\n";
$output .= "CREATE TABLE IF NOT EXISTS quotes (\n";
$output .= "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
$output .= "    quote_number VARCHAR(20) NOT NULL UNIQUE,\n";
$output .= "    customer_id INT,\n";
$output .= "    customer_email VARCHAR(100),\n";
$output .= "    total_amount DECIMAL(10,2),\n";
$output .= "    status ENUM('draft', 'sent', 'accepted', 'rejected') DEFAULT 'draft',\n";
$output .= "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
$output .= "    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL\n";
$output .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

foreach ($files as $file) {
    if ($file !== 'consolidated_setup.sql' && file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        
        // Remove any USE database statements
        $lines = explode("\n", $content);
        $filtered_lines = array_filter($lines, function($line) {
            return stripos(trim($line), 'USE') !== 0;
        });
        
        $output .= "\n-- Including file: $file\n";
        $output .= implode("\n", $filtered_lines);
        $output .= "\n\n";
    }
}

$output .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

// Write to combined file
file_put_contents(__DIR__ . '/combined_setup.sql', $output);
echo "Combined SQL file has been created successfully!\n";
?>
