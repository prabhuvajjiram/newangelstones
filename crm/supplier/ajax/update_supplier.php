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
$required_fields = ['supplierId', 'companyName', 'contactPerson', 'email', 'phone', 'status'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        sendJsonResponse(false, 'Missing required field: ' . $field);
        exit;
    }
}

try {
    $pdo = getDbConnection();
    $query = "UPDATE suppliers 
              SET company_name = :company_name,
                  contact_person = :contact_person,
                  email = :email,
                  phone = :phone,
                  address = :address,
                  notes = :notes,
                  status = :status,
                  updated_at = NOW()
              WHERE id = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id' => sanitizeInput($_POST['supplierId']),
        'company_name' => sanitizeInput($_POST['companyName']),
        'contact_person' => sanitizeInput($_POST['contactPerson']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'notes' => sanitizeInput($_POST['notes'] ?? ''),
        'status' => sanitizeInput($_POST['status'])
    ]);
    
    sendJsonResponse(true, 'Supplier updated successfully');
} catch (PDOException $e) {
    error_log("Database error in update_supplier.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred');
}
