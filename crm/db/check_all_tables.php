<?php
require_once '../includes/config.php';

try {
    // Get all tables in the database
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Database Tables in {$db_name}</h2>";
    echo "<pre>";
    
    foreach ($tables as $table) {
        echo "\nTable: {$table}\n";
        echo str_repeat("-", strlen($table) + 7) . "\n";
        
        // Get table structure
        $columns = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "{$column['Field']}: {$column['Type']}";
            if ($column['Key'] === 'PRI') echo " (Primary Key)";
            if ($column['Key'] === 'MUL') echo " (Foreign Key)";
            echo "\n";
        }
        
        // Get row count
        $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        echo "Row count: {$count}\n\n";
    }
    
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
