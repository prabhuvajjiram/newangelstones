<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    // Get raw POST data and decode
    $raw_data = file_get_contents('php://input');
    $data = json_decode($raw_data, true);
    
    if ($data === null) {
        throw new Exception('Invalid JSON data received');
    }
    
    if (!isset($data['action'])) {
        throw new Exception('No action specified');
    }
    
    if (empty($data['name'])) {
        throw new Exception('Name is required');
    }
    
    // Prepare the SQL based on action
    if ($data['action'] === 'add') {
        $sql = "INSERT INTO customers (
                    name, email, phone, address, city, state, 
                    postal_code, notes, company_id, job_title
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
        $params = [
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['notes'],
            $data['company_id'],
            $data['job_title']
        ];
    } elseif ($data['action'] === 'update') {
        if (empty($data['id'])) {
            throw new Exception('Customer ID is required for update');
        }
        
        $sql = "UPDATE customers SET 
                    name = ?, email = ?, phone = ?, address = ?, 
                    city = ?, state = ?, postal_code = ?, notes = ?,
                    company_id = ?, job_title = ?
                WHERE id = ?";
        $params = [
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['notes'],
            $data['company_id'],
            $data['job_title'],
            $data['id']
        ];
    } else {
        throw new Exception('Invalid action specified');
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($data['action'] === 'add') {
        $response = ['success' => true, 'id' => $pdo->lastInsertId()];
    } else {
        $response = ['success' => true];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
