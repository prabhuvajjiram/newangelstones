<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/../db/migrations/create_customers_companies_tables.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Migration completed successfully!\n";
    echo "Tables created: customers, companies\n";
    
} catch (PDOException $e) {
    die("Error running migration: " . $e->getMessage() . "\n");
}
