<?php
require_once '../includes/config.php';
require_once '../includes/crm_functions.php';
require_once '../session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $documentManager = getCRMInstance('DocumentManagement');
    
    // Check if file was uploaded successfully
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    $data = [
        'customer_id' => $_POST['customer_id'],
        'quote_id' => $_POST['quote_id'] ?? null,
        'document_type' => $_POST['document_type'],
        'uploaded_by' => $_SESSION['user_id'],
        'notes' => $_POST['notes']
    ];

    if ($documentManager->uploadDocument($_FILES['document'], $data)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to upload document');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
