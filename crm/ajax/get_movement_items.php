<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

try {
    $db = getDBConnection();
    $params = [];
    $where_conditions = [];
    
    // Get search term if provided
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if (!empty($search)) {
        $params['search'] = "%{$search}%";
    }
    
    // Get filter values
    $item_type = isset($_GET['item_type']) ? $_GET['item_type'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $color = isset($_GET['color']) ? $_GET['color'] : '';
    $warehouse = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';
    
    // Get finished products
    $fpQuery = "
        SELECT 
            fp.id,
            'finished_product' as item_type,
            CONCAT(fp.sku, ' - ', fp.name) as display_name
        FROM finished_products fp
        LEFT JOIN finished_products_inventory fpi ON fp.id = fpi.product_id
        WHERE fp.status = 'active'";
    
    if (!empty($search)) {
        $fpQuery .= " AND (fp.sku LIKE :search OR fp.name LIKE :search)";
    }
    if (!empty($category)) {
        $fpQuery .= " AND fp.category_id = :category";
        $params['category'] = $category;
    }
    if (!empty($warehouse)) {
        $fpQuery .= " AND fpi.warehouse_id = :fp_warehouse";
        $params['fp_warehouse'] = $warehouse;
    }
    
    // Get raw materials
    $rmQuery = "
        SELECT 
            rm.id,
            'raw_material' as item_type,
            CONCAT(
                'Raw Material: ',
                scr.color_name,
                ' (',
                rm.length, 'x', rm.width, 'x', rm.height,
                ' - Location: ',
                COALESCE(rm.location_details, 'Not specified'),
                ')'
            ) as display_name
        FROM raw_materials rm
        LEFT JOIN stone_color_rates scr ON rm.color_id = scr.id
        WHERE 1=1";
    
    if (!empty($search)) {
        $rmQuery .= " AND (scr.color_name LIKE :search OR rm.location_details LIKE :search)";
    }
    if (!empty($color)) {
        $rmQuery .= " AND rm.color_id = :color";
        $params['color'] = $color;
    }
    if (!empty($warehouse)) {
        $rmQuery .= " AND rm.warehouse_id = :rm_warehouse";
        $params['rm_warehouse'] = $warehouse;
    }
    
    // Combine queries based on item type filter
    $finalQuery = "";
    if (empty($item_type) || $item_type === 'finished_product') {
        $finalQuery .= $fpQuery;
    }
    if (empty($item_type) || $item_type === 'raw_material') {
        if (!empty($finalQuery)) {
            $finalQuery .= " UNION ";
        }
        $finalQuery .= $rmQuery;
    }
    
    $finalQuery .= " ORDER BY display_name";
    
    $stmt = $db->prepare($finalQuery);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $items]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
