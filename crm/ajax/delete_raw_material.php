<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Material ID is required');
    }

    $material_id = intval($_POST['id']);

    $pdo->beginTransaction();

    try {
        // Check if material exists
        $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE id = ?");
        $stmt->execute([$material_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Material not found');
        }

        // Check if material has stock movements
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM stock_movements WHERE material_id = ?");
        $stmt->execute([$material_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            // Material has stock movements - mark it as deleted instead
            $stmt = $pdo->prepare("
                UPDATE raw_materials 
                SET status = 'deleted',
                    quantity = 0,
                    last_updated = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$material_id]);

            // Record the status change in stock movements
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements 
                (material_id, movement_type, quantity, notes, created_by)
                VALUES (?, 'adjustment', 0, 'Material marked as deleted', ?)
            ");
            $stmt->execute([$material_id, $_SESSION['email'] ?? 'system']);

            $message = 'Material has been marked as deleted due to existing stock movements';
        } else {
            // No stock movements - safe to delete
            $stmt = $pdo->prepare("DELETE FROM raw_materials WHERE id = ?");
            $stmt->execute([$material_id]);
            $message = 'Material deleted successfully';
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => $message
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, 'foreign key constraint fails') !== false) {
        $errorMessage = 'Cannot delete this material as it is referenced by other records. Please archive it instead.';
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
}
