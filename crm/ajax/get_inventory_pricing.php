<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get request parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    // Prepare base query
    if ($type === 'finished_product') {
        $baseQuery = "
            FROM finished_products fp
            LEFT JOIN product_categories pc ON fp.category_id = pc.id
            LEFT JOIN stone_color_rates scr ON fp.color_id = scr.id
            WHERE fp.status = 'active'
        ";
        
        $columns = [
            'fp.sku', 'fp.name', 'pc.name', 'scr.color_name', 
            'fp.length', 'fp.unit_price', 'fp.final_price'
        ];
        
        $selectQuery = "
            SELECT 
                fp.*, 
                pc.name as category_name,
                scr.color_name
        ";
    } else {
        $baseQuery = "
            FROM raw_materials rm
            LEFT JOIN stone_color_rates scr ON rm.color_id = scr.id
            WHERE rm.status != 'out_of_stock'
        ";
        
        $columns = [
            'rm.id', 'scr.color_name', 'rm.length', 'rm.warehouse_name', 
            'rm.quantity', 'rm.unit_price', 'rm.final_price'
        ];
        
        $selectQuery = "
            SELECT 
                rm.*,
                scr.color_name
        ";
    }

    // Count total records
    $countQuery = "SELECT COUNT(*) as count " . $baseQuery;
    $stmt = $db->prepare($countQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $filteredRecords = $totalRecords; // Will be different if search is implemented

    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
    $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    
    if (isset($columns[$orderColumn])) {
        $orderBy = " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    } else {
        $orderBy = " ORDER BY " . $columns[0] . " ASC";
    }

    // Final query with pagination
    $query = $selectQuery . $baseQuery . $orderBy . " LIMIT ?, ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $start, PDO::PARAM_INT);
    $stmt->bindValue(2, $length, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Error in get_inventory_pricing.php: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred while fetching the data'
    ]);
}
