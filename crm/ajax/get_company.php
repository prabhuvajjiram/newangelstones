<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Company ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT cu.id) as contact_count,
               SUM(CASE WHEN cu.lead_score >= 70 THEN 1 ELSE 0 END) as hot_leads_count
        FROM companies c
        LEFT JOIN customers cu ON c.id = cu.company_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    
    $stmt->execute([$_GET['id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        http_response_code(404);
        echo json_encode(['error' => 'Company not found']);
        exit;
    }
    
    echo json_encode($company);
    
} catch (PDOException $e) {
    error_log("Database Error in get_company.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
