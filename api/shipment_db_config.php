<?php
/**
 * Shipment Tracking Database Configuration
 * 
 * This file contains database connection details for the shipment tracking system.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'angelstones_quotes_new');

/**
 * Get a new database connection
 * 
 * @return PDO A PDO database connection
 * @throws Exception If connection fails
 */
function getShipmentDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch(PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}
?>
