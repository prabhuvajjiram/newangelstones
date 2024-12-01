<?php
require_once '../includes/config.php';
require_once '../session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    // Validate required fields
    if (empty($_POST['customerId']) || empty($_POST['date']) || empty($_POST['status'])) {
        http_response_code(400);
        exit('Missing required fields');
    }

    // Insert follow-up record
    $stmt = $pdo->prepare("
        INSERT INTO follow_ups (
            customer_id, user_id, follow_up_date, 
            status, notes
        ) VALUES (
            :customer_id, :user_id, :follow_up_date,
            :status, :notes
        )
    ");

    $result = $stmt->execute([
        'customer_id' => $_POST['customerId'],
        'user_id' => $_SESSION['user_id'],
        'follow_up_date' => $_POST['date'],
        'status' => $_POST['status'],
        'notes' => $_POST['notes'] ?? null
    ]);

    if ($result) {
        // Update customer's last_follow_up_date
        $updateStmt = $pdo->prepare("
            UPDATE customers 
            SET last_follow_up_date = :date 
            WHERE id = :customer_id
        ");
        $updateStmt->execute([
            'date' => $_POST['date'],
            'customer_id' => $_POST['customerId']
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to save follow-up');
    }

} catch (Exception $e) {
    error_log("Error in follow_up.php: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save follow-up: ' . $e->getMessage()
    ]);
}
