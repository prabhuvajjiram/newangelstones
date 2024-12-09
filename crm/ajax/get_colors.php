<?php
// Prevent PHP errors from being displayed
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Verify database connection
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }

    // Check if stone_color_rates table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'stone_color_rates'");
    if ($stmt->rowCount() == 0) {
        // Create stone_color_rates table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS stone_color_rates (
                id INT PRIMARY KEY AUTO_INCREMENT,
                color_name VARCHAR(100) NOT NULL,
                price_increase_percentage DECIMAL(10,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Insert default colors if table was just created
        $pdo->exec("
            INSERT INTO stone_color_rates (color_name, price_increase_percentage) VALUES 
            ('Black', 0.00),
            ('Coffee Brown', 7.00),
            ('Star Galaxy Black', 40.00),
            ('Bahama Blue', 0.00),
            ('NH Red', 20.00),
            ('Cats Eye', 20.00),
            ('Brown Wood', 40.00),
            ('SF Impala', 65.00),
            ('Blue Pearl', 100.00),
            ('Emeral Pearl', 100.00),
            ('rainforest Green', 45.00),
            ('Brazil Gold', 35.00),
            ('Grey', 0.00)
        ");
    }

    // Get all colors
    $stmt = $pdo->query("
        SELECT id, color_name, price_increase_percentage 
        FROM stone_color_rates 
        ORDER BY color_name ASC
    ");
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'colors' => $colors
    ]);

} catch (Exception $e) {
    error_log("Error in get_colors.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
