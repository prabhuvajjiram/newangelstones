<?php
require_once('../includes/config.php');
require_once('../includes/db_config.php');

try {
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/migrations/create_models_table.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Models table created and populated successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
