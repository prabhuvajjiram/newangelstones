<?php
require_once '../session_check.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Customer ID is required']);
    exit;
}

try {
    // First, let's verify the database connection
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }

    // Basic query first to test
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }

    // Now try the join query if basic query works
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            comp.name as company_name,
            comp.id as company_id
        FROM customers c 
        LEFT JOIN companies comp ON c.company_id = comp.id 
        WHERE c.id = ?
    ");
    
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode($customer);
    
} catch (PDOException $e) {
    error_log("Database Error in get_customer.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    error_log("General Error in get_customer.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
