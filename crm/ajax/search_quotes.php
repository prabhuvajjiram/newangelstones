<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $query = "SELECT q.*, c.name as customer_name, c.email as customer_email 
              FROM quotes q 
              LEFT JOIN customers c ON q.customer_id = c.id 
              WHERE 1=1";
    $params = array();
    
    // Add customer name filter
    if (!empty($data['customerName'])) {
        $query .= " AND c.name LIKE ?";
        $params[] = '%' . $data['customerName'] . '%';
    }
    
    // Add quote number filter
    if (!empty($data['quoteNumber'])) {
        $query .= " AND q.quote_number LIKE ?";
        $params[] = '%' . $data['quoteNumber'] . '%';
    }
    
    // Add date range filter
    if (!empty($data['dateFrom'])) {
        $query .= " AND DATE(q.created_at) >= ?";
        $params[] = $data['dateFrom'];
    }
    if (!empty($data['dateTo'])) {
        $query .= " AND DATE(q.created_at) <= ?";
        $params[] = $data['dateTo'];
    }
    
    // Add user role check
    if (!isAdmin()) {
        $query .= " AND q.username = ?";
        $params[] = $_SESSION['email'];
    }
    
    $query .= " ORDER BY q.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'quotes' => $quotes
    ]);

} catch (Exception $e) {
    error_log("Error in search_quotes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error searching quotes: ' . $e->getMessage()
    ]);
}
