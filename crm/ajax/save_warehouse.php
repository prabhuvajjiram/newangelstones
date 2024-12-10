<?php
// Start output buffering to catch any unwanted output
ob_start();
require_once '../includes/config.php';
require_once '../session_check.php';
requireAdmin();
// Clear any output that might have occurred during includes
ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        throw new Exception('Warehouse name is required');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $contact_person = isset($_POST['contact_person']) ? trim($_POST['contact_person']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

    $pdo->beginTransaction();

    try {
        if ($id) {
            // Update existing warehouse
            $stmt = $pdo->prepare("
                UPDATE warehouses 
                SET name = ?, address = ?, contact_person = ?, phone = ?, 
                    email = ?, notes = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $address, $contact_person, $phone, $email, $notes, $status, $id]);
            $message = 'Warehouse updated successfully';
        } else {
            // Insert new warehouse
            $stmt = $pdo->prepare("
                INSERT INTO warehouses (name, address, contact_person, phone, email, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $address, $contact_person, $phone, $email, $notes, $status]);
            $message = 'Warehouse added successfully';
        }

        $pdo->commit();
        $_SESSION['success_message'] = $message;
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// End output buffering and flush
ob_end_flush();
