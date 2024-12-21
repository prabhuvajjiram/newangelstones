<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check for admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Start transaction
    $pdo->beginTransaction();

    try {
        // Get invoice file path before deleting
        $stmt = $pdo->prepare("SELECT file_path FROM supplier_invoices WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            throw new Exception('Invoice not found');
        }

        // Delete invoice record
        $stmt = $pdo->prepare("DELETE FROM supplier_invoices WHERE id = ?");
        $stmt->execute([$_POST['id']]);

        // Delete file if it exists
        $filePath = '../uploads/supplier_invoices/' . $invoice['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Commit transaction
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error deleting invoice: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error deleting invoice']);
    exit;
}
