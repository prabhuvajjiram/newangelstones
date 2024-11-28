<?php
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    if (isset($_GET['id'])) {
        // Get single customer
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        
        if ($customer) {
            echo json_encode($customer);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Customer not found']);
        }
    } else {
        // Get all customers
        $customers = [];
        $result = $conn->query("SELECT * FROM customers ORDER BY name");
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        echo json_encode($customers);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
