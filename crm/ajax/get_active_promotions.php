<?php
header('Content-Type: application/json');
require_once('../includes/functions.php');

try {
    $pdo = getDbConnection();
    
    // Get limit parameter, default to all promotions
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    
    $query = "SELECT * FROM promotions 
              WHERE is_active = 1 
              AND start_date <= NOW() 
              AND end_date >= NOW() 
              ORDER BY start_date DESC";
    
    // Add limit if specified
    if ($limit > 0) {
        $query .= " LIMIT :limit";
    }
    
    $stmt = $pdo->prepare($query);
    
    if ($limit > 0) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If limit=1 was requested, return single promotion format
    if ($limit === 1) {
        echo json_encode([
            'success' => true,
            'promotion' => !empty($promotions) ? $promotions[0] : null
        ]);
    } else {
        // Return array of promotions
        echo json_encode([
            'success' => true,
            'promotions' => $promotions
        ]);
    }
} catch (Exception $e) {
    error_log("Error in get_active_promotions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
