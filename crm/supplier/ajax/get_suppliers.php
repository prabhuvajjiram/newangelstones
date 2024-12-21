<?php
$baseDir = dirname(dirname(dirname(__FILE__)));
require_once $baseDir . '/includes/session.php';
require_once $baseDir . '/includes/config.php';
require_once $baseDir . '/includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    sendJsonResponse(false, 'Unauthorized access');
    exit;
}

try {
    $pdo = getDbConnection();
    $query = "SELECT * FROM suppliers ORDER BY name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(true, '', $suppliers);
} catch (PDOException $e) {
    error_log("Database error in get_suppliers.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred');
    exit;
}
