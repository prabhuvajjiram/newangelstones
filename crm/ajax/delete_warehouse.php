<?php
ob_start();
require_once '../includes/config.php';
require_once '../session_check.php';
requireAdmin();
ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Warehouse ID is required');
    }

    $id = intval($_POST['id']);

    // Check if warehouse has any materials
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM raw_materials WHERE warehouse_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        throw new Exception('Cannot delete warehouse that contains materials. Please move or remove materials first.');
    }

    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM warehouses WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Warehouse not found');
        }

        $pdo->commit();
        $_SESSION['success_message'] = 'Warehouse deleted successfully';
        echo json_encode(['success' => true, 'message' => 'Warehouse deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
