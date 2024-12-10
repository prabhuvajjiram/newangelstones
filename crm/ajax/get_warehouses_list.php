<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

try {
    $db = getDBConnection();
    
    $query = "SELECT id, name FROM warehouses WHERE status = 'active' ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $warehouses]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
