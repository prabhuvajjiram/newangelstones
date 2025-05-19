<?php
// Simple script to check database structure
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Connect to the database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Get list of tables
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    // Get structure of chat-related tables
    $structure = [];
    foreach ($tables as $table) {
        if (strpos($table, 'chat_') === 0) {
            $columns = [];
            $result = $db->query("DESCRIBE `$table`");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row;
            }
            $structure[$table] = $columns;
        }
    }
    
    // Check for required tables
    $requiredTables = ['chat_sessions', 'chat_messages', 'chat_teams', 'chat_settings'];
    $missingTables = [];
    
    foreach ($requiredTables as $requiredTable) {
        if (!in_array($requiredTable, $tables)) {
            $missingTables[] = $requiredTable;
        }
    }
    
    echo json_encode([
        'all_tables' => $tables,
        'chat_tables_structure' => $structure,
        'missing_tables' => $missingTables,
        'database_ready' => empty($missingTables)
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
