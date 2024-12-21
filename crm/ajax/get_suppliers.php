<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    
    // Get active suppliers
    $query = "SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'suppliers' => $suppliers
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching suppliers: ' . $e->getMessage()
    ]);
}
