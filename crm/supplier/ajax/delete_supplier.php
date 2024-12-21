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

if (!isset($_POST['id']) || empty($_POST['id'])) {
    sendJsonResponse(false, 'Supplier ID is required');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // First check if supplier has any associated invoices
    $query = "SELECT COUNT(*) FROM supplier_invoices WHERE supplier_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => sanitizeInput($_POST['id'])]);
    $invoiceCount = $stmt->fetchColumn();
    
    if ($invoiceCount > 0) {
        // If supplier has invoices, just mark them as inactive instead of deleting
        $query = "UPDATE suppliers SET status = 'inactive', updated_at = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => sanitizeInput($_POST['id'])]);
        
        sendJsonResponse(true, 'Supplier has associated invoices and has been marked as inactive instead of being deleted');
    } else {
        // If no invoices, we can safely delete the supplier
        $query = "DELETE FROM suppliers WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => sanitizeInput($_POST['id'])]);
        
        sendJsonResponse(true, 'Supplier deleted successfully');
    }
} catch (PDOException $e) {
    error_log("Database error in delete_supplier.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred');
}
