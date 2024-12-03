<?php
require_once '../includes/db_config.php';

// Function to run SQL file
function runSQLFile($filename, $pdo) {
    echo "Running $filename...\n";
    $sql = file_get_contents($filename);
    try {
        // Split SQL by semicolon to execute multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        echo "Successfully executed $filename\n";
    } catch (PDOException $e) {
        echo "Error executing $filename: " . $e->getMessage() . "\n";
    }
}

try {
    // Set longer timeout for remote connection
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 60,  // 60 seconds timeout
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    );

    // Connect to database with options
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    echo "Connecting to database at " . DB_HOST . "...\n";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "Connected to database successfully\n";

    // Order of execution
    $files = [
        'backup_angelstones_quotes_new.sql',    // Main database structure and data
        'consolidated_setup.sql',                // Additional setup
        'create_oauth_admin.sql',               // OAuth related tables
        'create_settings_tables.sql',           // Settings tables
        'update_admin_role.sql',                // Update admin roles
        'update_roles.sql',                     // Update user roles
        'update_super_admin.sql'                // Set up super admin
    ];

    // Execute each file
    foreach ($files as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            runSQLFile(__DIR__ . '/' . $file, $pdo);
        } else {
            echo "Warning: File $file not found\n";
        }
    }

    echo "Setup completed successfully!\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "DSN used: mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . "\n";
}
?>
