<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    $pdo = getDbConnection();
    
    // Debug connection info
    error_log("Database connected successfully in get_categories.php");
    
    $query = "SELECT * FROM product_categories ORDER BY name ASC";
    error_log("Executing query: " . $query);
    
    $stmt = $pdo->query($query);
    $categories = $stmt->fetchAll();
    
    error_log("Found " . count($categories) . " categories");
    error_log("Categories: " . print_r($categories, true));
    
    header('Content-Type: application/json');
    $response = [
        'success' => true,
        'categories' => $categories
    ];
    echo json_encode($response);
    error_log("Response sent: " . json_encode($response));
    
} catch (Exception $e) {
    error_log("Error in get_categories.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    $error_response = [
        'success' => false,
        'message' => "Error loading categories: " . $e->getMessage()
    ];
    echo json_encode($error_response);
}
