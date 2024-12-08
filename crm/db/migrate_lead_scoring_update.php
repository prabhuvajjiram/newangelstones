<?php
require_once '../includes/config.php';

try {
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/migrations/update_lead_scoring_rules.sql');
    $pdo->exec($sql);
    
    echo "Successfully updated lead scoring rules table structure.\n";
    
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
