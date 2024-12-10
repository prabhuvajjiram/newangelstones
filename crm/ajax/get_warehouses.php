<?php
// Start output buffering to catch any unwanted output
ob_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear any output that might have occurred during includes
ob_clean();

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            address,
            contact_person,
            phone,
            email,
            notes,
            status
        FROM warehouses 
        ORDER BY name ASC
    ");
    
    $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug output
    error_log('Warehouses data: ' . print_r($warehouses, true));
    
    echo json_encode([
        'success' => true,
        'data' => $warehouses
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_warehouses.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering and flush
ob_end_flush();
