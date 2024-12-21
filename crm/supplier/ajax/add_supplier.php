<?php
$baseDir = dirname(dirname(dirname(__FILE__)));
require_once $baseDir . '/includes/session.php';
require_once $baseDir . '/includes/config.php';
require_once $baseDir . '/includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    sendJsonResponse(false, 'Unauthorized access');
    exit;
}

// Validate required fields
$required_fields = ['companyName', 'contactPerson', 'email', 'phone'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        sendJsonResponse(false, 'Missing required field: ' . $field);
        exit;
    }
}

try {
    $pdo = getDbConnection();
    $query = "INSERT INTO suppliers (name, contact_person, email, phone, address, notes) 
              VALUES (:name, :contact_person, :email, :phone, :address, :notes)";
    
    $stmt = $pdo->prepare($query);
    $params = [
        'name' => sanitizeInput($_POST['companyName']),
        'contact_person' => sanitizeInput($_POST['contactPerson']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'address' => isset($_POST['address']) ? sanitizeInput($_POST['address']) : '',
        'notes' => isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : ''
    ];
    
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(true, 'Supplier added successfully');
    } else {
        sendJsonResponse(false, 'Failed to add supplier');
    }
} catch (PDOException $e) {
    error_log("Database error in add_supplier.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred: ' . $e->getMessage());
    exit;
}
