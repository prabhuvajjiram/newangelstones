<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    global $pdo;
    
    // Get total records count
    $total_query = "SELECT COUNT(*) as count FROM customers";
    $total_result = $pdo->query($total_query);
    $total_records = $total_result->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get filtered records count
    $filtered_records = $total_records;
    
    // Handle search
    $search = $_POST['search']['value'] ?? '';
    $where_clause = '';
    
    if (!empty($search)) {
        $where_clause = " WHERE c.name LIKE :search 
                         OR c.email LIKE :search 
                         OR c.phone LIKE :search 
                         OR c.address LIKE :search 
                         OR c.city LIKE :search 
                         OR c.state LIKE :search 
                         OR c.postal_code LIKE :search 
                         OR comp.name LIKE :search";
        
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers c LEFT JOIN companies comp ON c.company_id = comp.id" . $where_clause);
        $search_param = "%$search%";
        $count_stmt->bindParam(':search', $search_param);
        $count_stmt->execute();
        $filtered_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    // Prepare the main query
    $query = "SELECT c.*, comp.name as company_name 
             FROM customers c 
             LEFT JOIN companies comp ON c.company_id = comp.id" . $where_clause;
    
    // Add ordering
    $order_column = $_POST['order'][0]['column'] ?? 1;
    $order_dir = $_POST['order'][0]['dir'] ?? 'asc';
    
    $order_columns = [
        0 => 'c.id',
        1 => 'c.name',
        2 => 'comp.name',
        3 => 'c.email',
        4 => 'c.city',
        5 => 'c.status'
    ];
    
    if (isset($order_columns[$order_column])) {
        $query .= " ORDER BY " . $order_columns[$order_column] . " $order_dir";
    }
    
    // Add pagination
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $query .= " LIMIT :start, :length";
    
    // Execute the query
    $stmt = $pdo->prepare($query);
    
    if (!empty($search)) {
        $stmt->bindParam(':search', $search_param);
    }
    
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'draw' => intval($_POST['draw']),
        'recordsTotal' => $total_records,
        'recordsFiltered' => $filtered_records,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in get_customers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
