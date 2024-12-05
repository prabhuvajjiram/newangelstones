<?php
require_once dirname(__FILE__) . '/../../includes/config.php';

try {
    // Check if columns exist
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'users' 
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME IN ('first_name', 'last_name')
    ");
    $stmt->execute();
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Add first_name column if it doesn't exist
    if (!in_array('first_name', $existing_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(255) DEFAULT NULL AFTER email");
        echo "Added first_name column\n";
    }

    // Add last_name column if it doesn't exist
    if (!in_array('last_name', $existing_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(255) DEFAULT NULL AFTER first_name");
        echo "Added last_name column\n";
    }

    echo "Migration completed successfully\n";
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
