<?php
// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    // Get database connection
    $pdo = getDbConnection();
    
    // Get colors from stone_color_rates table
    $stmt = $pdo->query("SELECT id, color_name FROM stone_color_rates ORDER BY color_name");
    
    $colors = array();
    while ($row = $stmt->fetch()) {
        $colors[] = array(
            'id' => $row['id'],
            'name' => $row['color_name']
        );
    }
    
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send JSON response
    echo json_encode(array(
        'success' => true,
        'colors' => $colors
    ));

} catch (PDOException $e) {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send error response
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => "Database error: " . $e->getMessage()
    ));
} catch (Exception $e) {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send error response
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}