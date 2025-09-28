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
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['notes'] ?? null,
            !empty($data['company_id']) ? $data['company_id'] : null,
            $data['job_title'] ?? null
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
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['notes'] ?? null,
            !empty($data['company_id']) ? $data['company_id'] : null,
            $data['job_title'] ?? null,
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
    
} catch (PDOException $e) {
    error_log("Database error in save_customer.php: " . $e->getMessage());
    
    // Handle specific database errors
    if ($e->getCode() == 23000) {
        if (strpos($e->getMessage(), 'customers_company_fk') !== false) {
            $error_message = 'Invalid company selected. Please select a valid company or leave it empty.';
        } else {
            $error_message = 'Database constraint violation. Please check your input data.';
        }
    } else {
        $error_message = 'Database error occurred while saving customer.';
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $error_message
    ]);
} catch (Exception $e) {
    error_log("General error in save_customer.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
