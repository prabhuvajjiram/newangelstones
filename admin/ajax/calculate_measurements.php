<?php
require_once '../includes/config.php';
require_once '../includes/modules/ProductCalculator.php';
require_once '../session_check.php';

requireLogin();

header('Content-Type: application/json');

try {
    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }
    
    // Required fields
    $required = ['type', 'size', 'model', 'length', 'breadth', 'quantity'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    // Get product details
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM products p
        WHERE p.type = ? AND p.size = ? AND p.model = ?
    ");
    $stmt->execute([$data['type'], $data['size'], $data['model']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Initialize calculator
    $calculator = new ProductCalculator($pdo);
    
    // Calculate measurements based on product type
    $type = strtolower($data['type']);
    $measurements = null;
    
    switch ($type) {
        case 'sertop':
            $measurements = $calculator->calculateSertopMeasurements(
                $product,
                $data['length'],
                $data['breadth'],
                $data['size'],
                $data['quantity']
            );
            break;
            
        case 'base':
            $measurements = $calculator->calculateBaseMeasurements(
                $product,
                $data['length'],
                $data['breadth'],
                $data['size'],
                $data['quantity']
            );
            break;
            
        case 'marker':
            $measurements = $calculator->calculateMarkerMeasurements(
                $product,
                $data['length'],
                $data['breadth'],
                $data['size'],
                $data['quantity']
            );
            break;
            
        case 'slant':
            $measurements = $calculator->calculateSlantMeasurements(
                $product,
                $data['length'],
                $data['breadth'],
                $data['size'],
                $data['quantity']
            );
            break;
            
        default:
            throw new Exception('Invalid product type');
    }
    
    // Round all values to 2 decimal places
    $measurements['sqft'] = round($measurements['sqft'], 2);
    $measurements['cubicFeet'] = round($measurements['cubicFeet'], 2);
    $measurements['basePrice'] = round($measurements['basePrice'], 2);
    
    // Calculate total price
    $measurements['totalPrice'] = round($measurements['basePrice'] * $data['quantity'], 2);
    
    // Return calculations
    echo json_encode([
        'success' => true,
        'measurements' => $measurements
    ]);
    
} catch (Exception $e) {
    error_log("Error in calculate_measurements.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
