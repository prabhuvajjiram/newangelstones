<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get and validate request parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    
    // Debug information
    error_log("Request parameters: " . json_encode($_POST));
    
    // Prepare base query
    if ($type === 'finished_product') {
        $baseQuery = "
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            LEFT JOIN stone_color_rates scr ON p.color_id = scr.id
            LEFT JOIN warehouses w ON p.location_id = w.id
            LEFT JOIN supplier_products sp ON sp.product_id = p.id
            LEFT JOIN (
                SELECT 
                    spp1.supplier_product_id,
                    spp1.unit_price,
                    spp1.markup_percentage,
                    spp1.effective_date,
                    spp1.end_date
                FROM supplier_product_prices spp1
                INNER JOIN (
                    SELECT supplier_product_id, MAX(effective_date) as latest_date
                    FROM supplier_product_prices 
                    WHERE effective_date <= CURRENT_DATE
                    AND (end_date IS NULL OR end_date > CURRENT_DATE)
                    GROUP BY supplier_product_id
                ) spp2 ON spp1.supplier_product_id = spp2.supplier_product_id 
                AND spp1.effective_date = spp2.latest_date
                AND (spp1.end_date IS NULL OR spp1.end_date > CURRENT_DATE)
            ) spp ON spp.supplier_product_id = sp.id
            WHERE 1=1
            GROUP BY p.id
        ";
        
        $selectQuery = "
            SELECT SQL_CALC_FOUND_ROWS
                p.id,
                p.sku,
                p.name,
                p.description,
                COALESCE(pc.name, 'Uncategorized') as category_name,
                COALESCE(scr.color_name, 'No Color') as color_name,
                COALESCE(p.length, 0) as length,
                COALESCE(p.width, 0) as width,
                COALESCE(p.height, 0) as height,
                COALESCE(spp.unit_price, 0) as unit_price,
                COALESCE(spp.markup_percentage, 80) as markup_percentage,
                COALESCE(ROUND(spp.unit_price * (1 + spp.markup_percentage / 100), 2), 0) as final_price,
                COALESCE(w.name, 'No Location') as warehouse_name,
                p.location_details,
                spp.effective_date
        ";
    } else {
        $baseQuery = "
            FROM raw_materials p
            LEFT JOIN stone_color_rates scr ON p.color_id = scr.id
            GROUP BY p.id
        ";
        
        $selectQuery = "
            SELECT SQL_CALC_FOUND_ROWS
                p.id,
                COALESCE(scr.color_name, 'No Color') as color_name,
                COALESCE(p.length, 0) as length,
                COALESCE(p.width, 0) as width,
                COALESCE(p.height, 0) as height,
                COALESCE(p.warehouse_name, 'No Location') as warehouse_name,
                p.location_details,
                p.quantity,
                COALESCE(p.unit_price, 0) as unit_price,
                CASE 
                    WHEN p.final_price > 0 THEN ROUND(((p.final_price / NULLIF(p.unit_price, 0)) - 1) * 100, 2)
                    ELSE 80 
                END as markup_percentage,
                COALESCE(p.final_price, ROUND(p.unit_price * 1.8, 2)) as final_price,
                p.last_updated as effective_date
        ";
    }
    
    // Get paginated data
    $query = $selectQuery . $baseQuery;
    if ($length > 0) {
        $query .= " ORDER BY p.id ASC LIMIT ?, ?";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $start, PDO::PARAM_INT);
    $stmt->bindParam(2, $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total records after SQL_CALC_FOUND_ROWS
    $totalRecords = $db->query("SELECT FOUND_ROWS()")->fetchColumn();
    $filteredRecords = $totalRecords;
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [],
        'trace' => $e->getTraceAsString()
    ]);
}
