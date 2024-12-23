<?php
// Start output buffering to catch any unwanted output
ob_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear any output that might have occurred during includes
ob_clean();

try {
    $pdo = getDbConnection();
    
    $query = "SELECT id, name, address, contact_person, phone, email, status FROM warehouses ORDER BY name ASC";
    $stmt = $pdo->query($query);
    $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $warehouses
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_warehouses.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error loading warehouses: " . $e->getMessage()
    ]);
}

// End output buffering and flush
ob_end_flush();
