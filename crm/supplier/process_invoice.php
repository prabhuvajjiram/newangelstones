<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Check for admin access
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $required_fields = ['supplier_id', 'invoice_number', 'invoice_date', 'currency', 'exchange_rate'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        if (!isset($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }

        // Validate file size (10MB limit)
        if ($_FILES['invoice_file']['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit');
        }

        // Get file extension and determine type
        $file_extension = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf' => 'pdf', 'xlsx' => 'excel', 'xls' => 'excel'];

        if (!isset($allowed_extensions[$file_extension])) {
            throw new Exception('Invalid file type. Only PDF and Excel files are allowed.');
        }

        $file_type = $allowed_extensions[$file_extension];

        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/supplier_invoices/' . date('Y/m');
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $filename = uniqid('invoice_') . '.' . $file_extension;
        $file_path = $upload_dir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['invoice_file']['tmp_name'], $file_path)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Insert invoice record
        $stmt = $pdo->prepare("
            INSERT INTO supplier_invoices (
                supplier_id, invoice_number, invoice_date, currency, 
                exchange_rate, file_path, file_type, created_at
            ) VALUES (
                :supplier_id, :invoice_number, :invoice_date, :currency,
                :exchange_rate, :file_path, :file_type, NOW()
            )
        ");

        $stmt->execute([
            'supplier_id' => $_POST['supplier_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_date' => $_POST['invoice_date'],
            'currency' => $_POST['currency'],
            'exchange_rate' => $_POST['exchange_rate'],
            'file_path' => $file_path,
            'file_type' => $file_type
        ]);

        $invoice_id = $pdo->lastInsertId();

        // Process the invoice based on file type
        $invoice = [
            'id' => $invoice_id,
            'supplier_id' => $_POST['supplier_id'],
            'file_path' => $file_path,
            'file_type' => $file_type
        ];

        if ($file_type === 'excel') {
            require_once 'processors/excel_processor.php';
            processExcelInvoice($invoice);
        } else {
            require_once 'processors/pdf_processor.php';
            processPDFInvoice($invoice);
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = 'Invoice uploaded and processed successfully';
        header('Location: supplier_invoice.php');
        exit;

    } catch (Exception $e) {
        // Rollback transaction if active
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Delete uploaded file if it exists
        if (isset($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }

        $_SESSION['error'] = 'Error processing invoice: ' . $e->getMessage();
        header('Location: supplier_invoice.php');
        exit;
    }
} else {
    header('Location: supplier_invoice.php');
    exit;
}
