<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Warehouse ID is required');
    }

    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = ?");
    $stmt->execute([$id]);
    $warehouse = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$warehouse) {
        throw new Exception('Warehouse not found');
    }

    echo json_encode(['success' => true, 'data' => $warehouse]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
