<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // SQL to add special_monument_id column
    $sql = "ALTER TABLE `quote_items`
            ADD COLUMN IF NOT EXISTS `special_monument_id` int(11) DEFAULT NULL,
            ADD CONSTRAINT `quote_items_special_monument_fk` 
            FOREIGN KEY (`special_monument_id`) 
            REFERENCES `special_monument` (`id`) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE;";

    // Execute the SQL
    $pdo->exec($sql);
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
