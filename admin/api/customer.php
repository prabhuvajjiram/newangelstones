<?php
require_once '../includes/config.php';
require_once '../session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Customer ID is required');
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM customers 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        http_response_code(404);
        exit('Customer not found');
    }

    header('Content-Type: application/json');
    echo json_encode($customer);

} catch (Exception $e) {
    error_log("Error in customer.php: " . $e->getMessage());
    http_response_code(500);
    exit('Internal server error');
}
