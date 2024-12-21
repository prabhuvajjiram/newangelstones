<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check for admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: supplier_invoice.php');
    exit;
}

// Validate file upload
if (!isset($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Error uploading file: ' . ($_FILES['invoice_file']['error'] ?? 'No file uploaded');
    header('Location: supplier_invoice.php');
    exit;
}

// Validate required fields
$required_fields = ['supplier_id', 'invoice_number', 'invoice_date', 'currency', 'total_amount'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = 'Missing required field: ' . $field;
        header('Location: supplier_invoice.php');
        exit;
    }
}

try {
    $pdo = getDbConnection();
    
    // Start transaction
    $pdo->beginTransaction();

    try {
        // Process file upload
        $file = $_FILES['invoice_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($fileExt, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        // Generate unique filename
        $filename = uniqid('invoice_') . '.' . $fileExt;
        $uploadDir = '../uploads/supplier_invoices/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $targetPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        // Insert invoice record
        $query = "INSERT INTO supplier_invoices (
            supplier_id, invoice_number, invoice_date, currency, 
            total_amount, file_path, file_type, status, created_at
        ) VALUES (
            :supplier_id, :invoice_number, :invoice_date, :currency,
            :total_amount, :file_path, :file_type, 'pending', NOW()
        )";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'supplier_id' => $_POST['supplier_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_date' => $_POST['invoice_date'],
            'currency' => $_POST['currency'],
            'total_amount' => $_POST['total_amount'],
            'file_path' => $filename,
            'file_type' => $fileExt
        ]);

        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = 'Invoice uploaded successfully';
        header('Location: supplier_invoice.php');
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Delete uploaded file if it exists
        if (isset($targetPath) && file_exists($targetPath)) {
            unlink($targetPath);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error processing invoice: " . $e->getMessage());
    $_SESSION['error'] = "Error processing invoice: " . $e->getMessage();
    header('Location: supplier_invoice.php');
    exit;
}
