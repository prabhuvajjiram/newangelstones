<?php
require_once '../includes/config.php';
require_once '../session_check.php';
requireAdmin();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    $response = [];
    
    if (isset($_GET['report_type'])) {
        switch ($_GET['report_type']) {
            case 'stock_level':
                // Get current stock levels for raw materials
                $stmt = $pdo->query("
                    SELECT 
                        CONCAT(rm.id, ' - ', sc.color_name, ' (', rm.length, 'x', rm.width, 'x', rm.height, ')') as product_name,
                        rm.quantity as current_stock,
                        rm.min_stock_level as minimum_stock,
                        rm.last_updated,
                        rm.warehouse_name,
                        rm.status
                    FROM raw_materials rm
                    JOIN stone_color_rates sc ON rm.color_id = sc.id
                    UNION ALL
                    SELECT 
                        CONCAT(fp.id, ' - ', fp.name) as product_name,
                        fpi.quantity as current_stock,
                        fpi.min_stock_level as minimum_stock,
                        fpi.last_updated,
                        w.name as warehouse_name,
                        fpi.status
                    FROM finished_products fp
                    JOIN finished_products_inventory fpi ON fp.id = fpi.product_id
                    JOIN warehouses w ON fpi.warehouse_id = w.id
                    ORDER BY product_name
                ");
                
                if (!$stmt) {
                    throw new Exception("Database error: " . $pdo->errorInfo()[2]);
                }
                
                $stock_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['data'] = $stock_data;
                break;

            case 'movement':
                $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $end_date = $_GET['end_date'] ?? date('Y-m-d');
                
                // Get movements for both raw materials and finished products
                $stmt = $pdo->prepare("
                    SELECT 
                        sm.created_at as date,
                        CONCAT(rm.id, ' - ', sc.color_name, ' (', rm.length, 'x', rm.width, 'x', rm.height, ')') as product_name,
                        sm.movement_type as type,
                        sm.quantity,
                        CONCAT(sm.reference_type, ': ', sm.reference_id) as reference,
                        sm.created_by,
                        'raw_material' as item_type
                    FROM stock_movements sm
                    JOIN raw_materials rm ON sm.material_id = rm.id
                    JOIN stone_color_rates sc ON rm.color_id = sc.id
                    WHERE DATE(sm.created_at) BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    SELECT 
                        fpm.created_at as date,
                        CONCAT(fp.id, ' - ', fp.name) as product_name,
                        fpm.movement_type as type,
                        fpm.quantity,
                        CONCAT(fpm.reference_type, ': ', fpm.reference_id) as reference,
                        fpm.created_by,
                        'finished_product' as item_type
                    FROM finished_products_movements fpm
                    JOIN finished_products fp ON fpm.product_id = fp.id
                    WHERE DATE(fpm.created_at) BETWEEN ? AND ?
                    
                    ORDER BY date DESC
                ");
                
                if (!$stmt) {
                    throw new Exception("Database error: " . $pdo->errorInfo()[2]);
                }
                
                $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
                $movement_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['data'] = $movement_data;
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
        
        $response['success'] = true;
    } else {
        throw new Exception('Report type not specified');
    }
} catch (Exception $e) {
    error_log("Inventory Report Error: " . $e->getMessage());
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
