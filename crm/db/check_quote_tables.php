<?php
$db_host = '127.0.0.1';
$db_name = 'angelstones_quotes_new';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['quotes', 'quote_items', 'quote_history'];
    
    foreach ($tables as $table) {
        echo "\n-- $table Table Structure\n";
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $result['Create Table'] . ";\n";
        
        // Check foreign keys
        echo "\n-- Foreign Keys for $table:\n";
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "-- " . $row['CONSTRAINT_NAME'] . ": " . 
                 $row['COLUMN_NAME'] . " -> " . 
                 $row['REFERENCED_TABLE_NAME'] . "(" . 
                 $row['REFERENCED_COLUMN_NAME'] . ")\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
