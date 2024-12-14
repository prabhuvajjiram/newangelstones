<?php
require_once '../includes/config.php';

try {
    // Create PDO connection
    $dsn = "mysql:host=$db_host;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Array of SQL files to execute in order
    $sqlFiles = [
        'inventory_tables.sql',
        'finished_products_tables.sql',
        'order_tables.sql'
    ];

    // Execute each SQL file
    foreach ($sqlFiles as $file) {
        echo "Executing $file...\n";
        $sql = file_get_contents(__DIR__ . '/' . $file);
        
        // Split the SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "Success: Executed statement\n";
                } catch (PDOException $e) {
                    // List of error codes to ignore
                    $ignoredErrors = [
                        '42S01', // Table already exists
                        '42000', // Duplicate key name
                        '23000'  // Duplicate entry
                    ];
                    
                    if (in_array($e->getCode(), $ignoredErrors)) {
                        echo "Notice: " . $e->getMessage() . " - Continuing...\n";
                        continue;
                    }
                    throw $e;
                }
            }
        }
        echo "Completed executing $file\n\n";
    }

    echo "Database setup completed successfully!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
