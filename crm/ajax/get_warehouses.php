<?php
// Start output buffering to catch any unwanted output
ob_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear any output that might have occurred during includes
ob_clean();

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM warehouses ORDER BY name");
    $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for DataTables
    $response = array(
        "data" => $warehouses
    );
    
    echo json_encode($response, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_THROW_ON_ERROR);
}

// End output buffering and flush
ob_end_flush();
