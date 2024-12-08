<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    // Log raw input for debugging
    $raw_input = file_get_contents('php://input');
    error_log("Raw input: " . $raw_input);
    
    // Get POST data
    $data = json_decode($raw_input, true);
    
    // Log decoded data
    error_log("Decoded data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }
    
    if (!isset($data['name']) || empty($data['name'])) {
        throw new Exception('Company name is required');
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO companies (
        name, industry, website, phone, employee_count, 
        annual_revenue, city, state, notes, address
    ) VALUES (
        :name, :industry, :website, :phone, :employee_count,
        :annual_revenue, :city, :state, :notes, :address
    )";
    
    // Log SQL for debugging
    error_log("SQL Query: " . $sql);
    
    // Prepare parameters
    $params = [
        'name' => $data['name'],
        'industry' => $data['industry'] ?? null,
        'website' => $data['website'] ?? null,
        'phone' => $data['phone'] ?? null,
        'employee_count' => $data['employee_count'] ?? null,
        'annual_revenue' => !empty($data['annual_revenue']) ? $data['annual_revenue'] : null,
        'city' => $data['city'] ?? null,
        'state' => $data['state'] ?? null,
        'notes' => $data['notes'] ?? null,
        'address' => $data['address'] ?? null
    ];
    
    // Log parameters
    error_log("Parameters: " . print_r($params, true));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $companyId = $pdo->lastInsertId();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Company saved successfully',
        'company_id' => $companyId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    error_log("Error in save_company.php: " . $e->getMessage());
}
