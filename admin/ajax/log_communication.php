<?php
require_once '../includes/config.php';
require_once '../includes/crm_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $communicationManager = getCRMInstance('CommunicationManagement');
    
    $data = [
        'customer_id' => $_POST['customer_id'],
        'user_id' => $_SESSION['user_id'],
        'type' => $_POST['type'],
        'subject' => $_POST['subject'],
        'content' => $_POST['content']
    ];

    if ($communicationManager->logCommunication($data)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to log communication');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
