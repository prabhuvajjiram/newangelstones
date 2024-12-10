<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    
    // First, let's check if we have any existing records and their structure
    $checkQuery = "SELECT * FROM product_movements LIMIT 1";
    $checkStmt = $pdo->query($checkQuery);
    $sampleRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
    error_log("Sample row: " . print_r($sampleRow, true));
    
    $query = "
        SELECT 
            m.*,
            w1.name as source_warehouse,
            w2.name as destination_warehouse,
            CASE m.item_type
                WHEN 'finished_product' THEN CONCAT(COALESCE(fp.sku, ''), ' - ', COALESCE(fp.name, ''))
                WHEN 'raw_material' THEN CONCAT(
                    'Raw Material: ',
                    COALESCE(scr.color_name, 'Unknown Color'),
                    ' - ',
                    COALESCE(rm.warehouse_name, ''),
                    ' (',
                    COALESCE(rm.length, 0), 'x',
                    COALESCE(rm.width, 0), 'x',
                    COALESCE(rm.height, 0),
                    ' - Location: ',
                    COALESCE(rm.location_details, 'Not specified'),
                    ')'
                )
                ELSE 'Unknown Item'
            END as item_name,
            rm.quantity as current_stock,
            rm.status as stock_status
        FROM product_movements m
        LEFT JOIN warehouses w1 ON m.source_warehouse_id = w1.id
        LEFT JOIN warehouses w2 ON m.destination_warehouse_id = w2.id
        LEFT JOIN finished_products fp ON m.item_type = 'finished_product' AND m.item_id = fp.id
        LEFT JOIN raw_materials rm ON m.item_type = 'raw_material' AND m.item_id = rm.id
        LEFT JOIN stone_color_rates scr ON rm.color_id = scr.id
        ORDER BY m.created_at DESC
        LIMIT 1000";

    error_log("Executing query: " . $query);
    
    $stmt = $pdo->query($query);
    if (!$stmt) {
        $error = $pdo->errorInfo();
        throw new Exception('Database error: ' . implode(', ', $error));
    }
    
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($movements) . " movements");

    $data = [];
    foreach ($movements as $movement) {
        $data[] = [
            'created_at' => $movement['created_at'],
            'item_type' => ucfirst(str_replace('_', ' ', $movement['item_type'] ?? 'Unknown')),
            'item_name' => $movement['item_name'] ?? 'Unknown Item',
            'movement_type' => ucfirst($movement['movement_type']),
            'quantity' => $movement['quantity'],
            'source_warehouse' => $movement['source_warehouse'] ?? '-',
            'destination_warehouse' => $movement['destination_warehouse'] ?? '-',
            'reference' => $movement['reference_type'] . ($movement['reference_id'] ? ' #' . $movement['reference_id'] : ''),
            'created_by' => $movement['created_by'],
            'current_stock' => $movement['current_stock'] ?? 0,
            'stock_status' => $movement['stock_status'] ?? 'Unknown'
        ];
    }

    // Ensure we have the correct DataTables response format
    $response = [
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data ?: [] // Ensure data is always an array
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log('Error in get_product_movements.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(200); // Keep 200 for DataTables to handle the error
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred while fetching product movements: ' . $e->getMessage()
    ]);
    exit;
}
