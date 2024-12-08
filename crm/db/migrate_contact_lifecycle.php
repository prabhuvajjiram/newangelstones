<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db_config.php';

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Set PDO to use buffered queries
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/migrations/add_contact_lifecycle_management.sql');
    
    // Split SQL into individual statements and filter out empty ones
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt); }
    );
    
    // Execute each statement without transaction (since we have DDL statements)
    foreach ($statements as $statement) {
        try {
            // Execute each statement
            $stmt = $pdo->prepare($statement);
            $stmt->execute();
            $stmt->closeCursor(); // Close the cursor to free up resources
            echo "Executed successfully: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "Error executing statement: " . substr($statement, 0, 50) . "...\n";
            echo "Error message: " . $e->getMessage() . "\n";
            // Continue with next statement instead of stopping
            continue;
        }
    }
    
    echo "\nMigration completed successfully!\n";
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
