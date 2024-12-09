<?php
// Prevent PHP errors from being displayed
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Verify database connection
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }

    $color = $_GET['color'] ?? '';
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';

    // First check if the tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'raw_materials'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Required tables are not set up. Please run the database setup script.");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'stone_color_rates'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Required tables are not set up. Please run the database setup script.");
    }

    $query = "
        SELECT 
            rm.*,
            scr.color_name
        FROM raw_materials rm
        JOIN stone_color_rates scr ON rm.color_id = scr.id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($color)) {
        $query .= " AND rm.color_id = ?";
        $params[] = $color;
    }

    if (!empty($status)) {
        $query .= " AND rm.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $query .= " AND (scr.color_name LIKE ? OR rm.location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY rm.last_updated DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'materials' => $materials
    ]);

} catch (Exception $e) {
    error_log("Error in get_raw_materials.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
