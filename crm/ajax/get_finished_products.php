<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    
    // Base query
    $query = "
        SELECT 
            fp.*,
            pc.name as category_name,
            COALESCE(SUM(i.quantity), 0) as total_stock,
            GROUP_CONCAT(DISTINCT w.name) as warehouse_names,
            GROUP_CONCAT(DISTINCT i.location_details) as location_details
        FROM finished_products fp
        LEFT JOIN product_categories pc ON fp.category_id = pc.id
        LEFT JOIN finished_products_inventory i ON fp.id = i.product_id
        LEFT JOIN warehouses w ON i.warehouse_id = w.id
        WHERE fp.status = 'active'";
    
    $params = [];
    
    // Apply filters
    if (!empty($_POST['category'])) {
        $query .= " AND fp.category_id = :category_id";
        $params['category_id'] = $_POST['category'];
    }
    
    if (!empty($_POST['color'])) {
        $query .= " AND fp.color_id = :color_id";
        $params['color_id'] = $_POST['color'];
    }
    
    // Search filter
    if (!empty($_POST['search']['value'])) {
        $search = $_POST['search']['value'];
        $query .= " AND (
            fp.sku LIKE :search 
            OR fp.name LIKE :search 
            OR pc.name LIKE :search
        )";
        $params['search'] = "%{$search}%";
    }
    
    // Status filter
    if (!empty($_POST['status'])) {
        switch ($_POST['status']) {
            case 'in_stock':
                $query .= " HAVING total_stock > 5";
                break;
            case 'low_stock':
                $query .= " HAVING total_stock > 0 AND total_stock <= 5";
                break;
            case 'out_of_stock':
                $query .= " HAVING total_stock = 0";
                break;
        }
    }
    
    // Group by before applying HAVING clause
    $query .= " GROUP BY fp.id";
    
    // Order
    $orderColumn = isset($_POST['order'][0]['column']) ? $_POST['order'][0]['column'] : 0;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
    
    $columns = [
        'fp.sku',
        'fp.name',
        'pc.name',
        'color_name',
        'dimensions',
        'total_stock',
        'status'
    ];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    }
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for DataTables
    $data = [];
    foreach ($products as $product) {
        $data[] = [
            'DT_RowId' => 'row_' . $product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
            'category_name' => $product['category_name'],
            'color_name' => 'N/A', // Temporary until we have colors table
            'dimensions' => $product['length'] . '×' . $product['width'] . '×' . $product['height'],
            'total_stock' => (int)$product['total_stock'],
            'status' => $product['total_stock'] <= 0 ? 'Out of Stock' : 
                       ($product['total_stock'] <= 5 ? 'Low Stock' : 'In Stock'),
            'actions' => '' // Will be rendered client-side
        ];
    }
    
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log('Error in get_finished_products.php: ' . $e->getMessage());
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}
