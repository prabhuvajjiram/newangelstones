<?php
// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    $pdo = getDbConnection();
    
    $query = "SELECT id, color_name as name FROM stone_color_rates ORDER BY color_name ASC";
    $stmt = $pdo->query($query);
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'colors' => $colors
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_colors.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error loading colors: " . $e->getMessage()
    ]);
}