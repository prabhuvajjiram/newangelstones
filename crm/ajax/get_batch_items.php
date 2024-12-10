<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    if (!in_array($type, ['finished_product', 'raw_material'])) {
        throw new Exception('Invalid item type');
    }

    if ($type === 'finished_product') {
        $query = "
            SELECT 
                fp.id,
                fp.name,
                COALESCE(SUM(fpi.quantity), 0) as quantity
            FROM finished_products fp
            LEFT JOIN finished_products_inventory fpi ON fp.id = fpi.product_id
            WHERE fp.status = 'active'
            GROUP BY fp.id, fp.name
            ORDER BY fp.name";
        $stmt = $pdo->query($query);
    } else {
        $query = "
            SELECT 
                rm.id,
                rm.name,
                COALESCE(SUM(rmi.quantity), 0) as quantity
            FROM raw_materials rm
            LEFT JOIN raw_materials_inventory rmi ON rm.id = rmi.material_id
            WHERE rm.status = 'active'
            GROUP BY rm.id, rm.name
            ORDER BY rm.name";
        $stmt = $pdo->query($query);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $items]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
