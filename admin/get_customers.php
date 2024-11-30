<?php
require_once 'includes/config.php';
require_once 'includes/db_config.php';

header('Content-Type: application/json');

try {
    // Fetch customers from database
    $stmt = $pdo->prepare("SELECT id, name, email FROM customers ORDER BY name");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Make sure to properly encode special characters
    array_walk_recursive($customers, function(&$item) {
        $item = htmlspecialchars_decode($item, ENT_QUOTES);
    });
    
    echo json_encode([
        'success' => true,
        'customers' => $customers
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
