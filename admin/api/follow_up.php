<?php
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $conn->prepare("INSERT INTO follow_ups (customer_id, follow_up_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", 
            $_POST['customerId'],
            $_POST['date'],
            $_POST['status'],
            $_POST['notes'],
            $_SESSION['user_id']
        );
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        // Get follow-ups for a customer
        $customerId = (int)$_GET['customer_id'];
        $stmt = $conn->prepare("SELECT f.*, u.username as created_by_name 
                               FROM follow_ups f 
                               LEFT JOIN users u ON f.created_by = u.id 
                               WHERE f.customer_id = ? 
                               ORDER BY f.follow_up_date DESC");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $followUps = [];
        while ($row = $result->fetch_assoc()) {
            $followUps[] = $row;
        }
        
        echo json_encode(['success' => true, 'follow_ups' => $followUps]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
