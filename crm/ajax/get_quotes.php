<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    // Build base query with proper joins and aggregations
    $query = "
        SELECT 
            q.*,
            c.name as customer_name,
            c.email as customer_email,
            COUNT(qi.id) as item_count,
            COALESCE(SUM(qi.cubic_feet), 0) as total_cubic_feet,
            CONCAT(u.first_name, ' ', u.last_name) as created_by_name
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id 
        LEFT JOIN users u ON q.username = u.email
        WHERE 1=1
    ";

    // Add role-based filtering
    if (!isAdmin()) {
        $query .= " AND q.username = :username";
    }

    // Group by to handle aggregations
    $query .= " GROUP BY q.id, c.name, c.email, u.first_name, u.last_name";
    
    // Order by most recent first
    $query .= " ORDER BY q.created_at DESC";

    $stmt = $pdo->prepare($query);
    
    // Bind parameters if needed
    if (!isAdmin()) {
        $stmt->bindParam(':username', $_SESSION['email']);
    }

    $stmt->execute();
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'quotes' => $quotes
    ]);

} catch (Exception $e) {
    error_log("Error in get_quotes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching quotes: ' . $e->getMessage()
    ]);
}
