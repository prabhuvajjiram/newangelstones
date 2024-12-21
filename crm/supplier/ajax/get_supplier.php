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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    sendJsonResponse(false, 'Supplier ID is required');
    exit;
}

try {
    $pdo = getDbConnection();
    $query = "SELECT * FROM suppliers WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => sanitizeInput($_GET['id'])]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($supplier) {
        sendJsonResponse(true, '', $supplier);
    } else {
        sendJsonResponse(false, 'Supplier not found');
    }
} catch (PDOException $e) {
    error_log("Database error in get_supplier.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred');
    exit;
}
