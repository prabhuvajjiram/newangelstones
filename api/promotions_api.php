<?php
require_once '../crm/includes/config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Get database connection - use global $pdo from config.php
global $pdo;
$db = $pdo;

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Helper function to generate unique ID
function generateId() {
    return 'promo_' . uniqid() . '_' . time();
}

// GET - Fetch all promotions or single promotion
if ($method === 'GET') {
    // Check if requesting single promotion
    $promotionId = $_GET['id'] ?? null;
    
    if ($promotionId) {
        // Fetch single promotion
        $stmt = $db->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$promotionId]);
        $promotion = $stmt->fetch();
        
        if (!$promotion) {
            sendResponse(['success' => false, 'error' => 'Promotion not found'], 404);
        }
        
        // Format promotion data
        $formattedPromotion = formatPromotion($promotion);
        sendResponse(['success' => true, 'promotion' => $formattedPromotion]);
    } else {
        // Fetch all promotions
        $platform = $_GET['platform'] ?? 'web';
        $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
        $archivedOnly = isset($_GET['archived']) && $_GET['archived'] === 'true';
        
        $sql = "SELECT * FROM promotions";
        $conditions = [];
        $params = [];
        
        if ($archivedOnly) {
            // Show archived promotions
            $conditions[] = "archived = 1";
        } else if ($activeOnly) {
            // Show only active, non-archived promotions
            $conditions[] = "enabled = 1";
            $conditions[] = "start_date <= NOW()";
            $conditions[] = "end_date >= NOW()";
            $conditions[] = "archived = 0";
        } else {
            // Show all non-archived (for admin)
            $conditions[] = "archived = 0";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY priority ASC, created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $promotions = $stmt->fetchAll();
        
        // Format promotions
        $formattedPromotions = array_map('formatPromotion', $promotions);
        
        sendResponse([
            'success' => true,
            'promotions' => $formattedPromotions,
            'settings' => [
                'autoRotateInterval' => 5000,
                'showDots' => true,
                'allowSwipe' => true,
                'cacheExpiry' => 3600
            ]
        ]);
    }
}

// POST - Create new promotion
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        sendResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
    }
    
    // Validate required fields
    $required = ['type', 'title', 'imageUrl', 'startDate', 'endDate'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(['success' => false, 'error' => "Missing required field: $field"], 400);
        }
    }
    
    // Generate ID
    $id = generateId();
    
    // Prepare SQL
    $sql = "INSERT INTO promotions (
        id, type, title, subtitle, description, image_url, link_url,
        start_date, end_date, priority, enabled,
        special_price, list_price, currency, display_format,
        product_code, color, tablet, base_info, features
    ) VALUES (
        :id, :type, :title, :subtitle, :description, :image_url, :link_url,
        :start_date, :end_date, :priority, :enabled,
        :special_price, :list_price, :currency, :display_format,
        :product_code, :color, :tablet, :base_info, :features
    )";
    
    $stmt = $db->prepare($sql);
    
    // Bind parameters
    $params = [
        ':id' => $id,
        ':type' => $data['type'],
        ':title' => $data['title'],
        ':subtitle' => $data['subtitle'] ?? null,
        ':description' => $data['description'] ?? null,
        ':image_url' => $data['imageUrl'],
        ':link_url' => $data['linkUrl'] ?? null,
        ':start_date' => $data['startDate'],
        ':end_date' => $data['endDate'],
        ':priority' => $data['priority'] ?? 1,
        ':enabled' => $data['enabled'] ?? true,
        ':special_price' => $data['pricing']['specialPrice'] ?? null,
        ':list_price' => $data['pricing']['listPrice'] ?? null,
        ':currency' => $data['pricing']['currency'] ?? 'USD',
        ':display_format' => $data['pricing']['displayFormat'] ?? null,
        ':product_code' => $data['productDetails']['productCode'] ?? null,
        ':color' => $data['productDetails']['color'] ?? null,
        ':tablet' => $data['productDetails']['tablet'] ?? null,
        ':base_info' => $data['productDetails']['base'] ?? null,
        ':features' => $data['productDetails']['features'] ?? null
    ];
    
    try {
        $stmt->execute($params);
        
        // Fetch created promotion
        $stmt = $db->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        $promotion = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'message' => 'Promotion created successfully',
            'promotion' => formatPromotion($promotion)
        ], 201);
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => 'Failed to create promotion', 'message' => $e->getMessage()], 500);
    }
}

// PUT - Update promotion
if ($method === 'PUT') {
    $promotionId = $_GET['id'] ?? null;
    
    if (!$promotionId) {
        sendResponse(['success' => false, 'error' => 'Promotion ID required'], 400);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        sendResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [':id' => $promotionId];
    
    $fieldMap = [
        'type' => 'type',
        'title' => 'title',
        'subtitle' => 'subtitle',
        'description' => 'description',
        'imageUrl' => 'image_url',
        'linkUrl' => 'link_url',
        'startDate' => 'start_date',
        'endDate' => 'end_date',
        'priority' => 'priority',
        'enabled' => 'enabled'
    ];
    
    foreach ($fieldMap as $jsonKey => $dbKey) {
        if (isset($data[$jsonKey])) {
            $updates[] = "$dbKey = :$dbKey";
            $params[":$dbKey"] = $data[$jsonKey];
        }
    }
    
    // Handle pricing
    if (isset($data['pricing'])) {
        if (isset($data['pricing']['specialPrice'])) {
            $updates[] = "special_price = :special_price";
            $params[':special_price'] = $data['pricing']['specialPrice'];
        }
        if (isset($data['pricing']['listPrice'])) {
            $updates[] = "list_price = :list_price";
            $params[':list_price'] = $data['pricing']['listPrice'];
        }
        if (isset($data['pricing']['currency'])) {
            $updates[] = "currency = :currency";
            $params[':currency'] = $data['pricing']['currency'];
        }
        if (isset($data['pricing']['displayFormat'])) {
            $updates[] = "display_format = :display_format";
            $params[':display_format'] = $data['pricing']['displayFormat'];
        }
    }
    
    // Handle product details
    if (isset($data['productDetails'])) {
        if (isset($data['productDetails']['productCode'])) {
            $updates[] = "product_code = :product_code";
            $params[':product_code'] = $data['productDetails']['productCode'];
        }
        if (isset($data['productDetails']['color'])) {
            $updates[] = "color = :color";
            $params[':color'] = $data['productDetails']['color'];
        }
        if (isset($data['productDetails']['tablet'])) {
            $updates[] = "tablet = :tablet";
            $params[':tablet'] = $data['productDetails']['tablet'];
        }
        if (isset($data['productDetails']['base'])) {
            $updates[] = "base_info = :base_info";
            $params[':base_info'] = $data['productDetails']['base'];
        }
        if (isset($data['productDetails']['features'])) {
            $updates[] = "features = :features";
            $params[':features'] = $data['productDetails']['features'];
        }
    }
    
    if (empty($updates)) {
        sendResponse(['success' => false, 'error' => 'No fields to update'], 400);
    }
    
    $sql = "UPDATE promotions SET " . implode(", ", $updates) . " WHERE id = :id";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Fetch updated promotion
        $stmt = $db->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$promotionId]);
        $promotion = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'message' => 'Promotion updated successfully',
            'promotion' => formatPromotion($promotion)
        ]);
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => 'Failed to update promotion', 'message' => $e->getMessage()], 500);
    }
}

// DELETE - Remove promotion and its image
if ($method === 'DELETE') {
    $promotionId = $_GET['id'] ?? null;
    
    if (!$promotionId) {
        sendResponse(['success' => false, 'error' => 'Promotion ID required'], 400);
    }
    
    try {
        // First, get the image URL to delete the file
        $stmt = $db->prepare("SELECT image_url FROM promotions WHERE id = ?");
        $stmt->execute([$promotionId]);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promotion) {
            sendResponse(['success' => false, 'error' => 'Promotion not found'], 404);
        }
        
        // Delete the database entry
        $stmt = $db->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->execute([$promotionId]);
        
        // Delete the image file if it exists
        if (!empty($promotion['image_url'])) {
            // Extract filename from URL (e.g., /images/promotions/file.webp -> file.webp)
            $imagePath = parse_url($promotion['image_url'], PHP_URL_PATH);
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
            
            // Delete file if it exists and is not the placeholder
            if (file_exists($fullPath) && strpos($imagePath, 'placeholder') === false) {
                unlink($fullPath);
            }
        }
        
        sendResponse(['success' => true, 'message' => 'Promotion and image deleted successfully']);
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => 'Failed to delete promotion', 'message' => $e->getMessage()], 500);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Failed to delete image file', 'message' => $e->getMessage()], 500);
    }
}

// PATCH - Toggle enabled status or archive/unarchive
if ($method === 'PATCH') {
    $promotionId = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? null;
    
    if (!$promotionId || !in_array($action, ['toggle', 'archive', 'unarchive'])) {
        sendResponse(['success' => false, 'error' => 'Invalid request'], 400);
    }
    
    try {
        if ($action === 'toggle') {
            // Get current status
            $stmt = $db->prepare("SELECT enabled FROM promotions WHERE id = ?");
            $stmt->execute([$promotionId]);
            $current = $stmt->fetch();
            
            if (!$current) {
                sendResponse(['success' => false, 'error' => 'Promotion not found'], 404);
            }
            
            // Toggle status
            $newStatus = !$current['enabled'];
            $stmt = $db->prepare("UPDATE promotions SET enabled = ? WHERE id = ?");
            $stmt->execute([$newStatus, $promotionId]);
            
            sendResponse([
                'success' => true,
                'message' => 'Promotion status updated',
                'enabled' => (bool)$newStatus
            ]);
        } else if ($action === 'archive') {
            // Archive promotion
            $stmt = $db->prepare("UPDATE promotions SET archived = 1, archived_at = NOW() WHERE id = ?");
            $stmt->execute([$promotionId]);
            
            if ($stmt->rowCount() === 0) {
                sendResponse(['success' => false, 'error' => 'Promotion not found'], 404);
            }
            
            sendResponse([
                'success' => true,
                'message' => 'Promotion archived successfully'
            ]);
        } else if ($action === 'unarchive') {
            // Unarchive promotion
            $stmt = $db->prepare("UPDATE promotions SET archived = 0, archived_at = NULL WHERE id = ?");
            $stmt->execute([$promotionId]);
            
            if ($stmt->rowCount() === 0) {
                sendResponse(['success' => false, 'error' => 'Promotion not found'], 404);
            }
            
            sendResponse([
                'success' => true,
                'message' => 'Promotion unarchived successfully'
            ]);
        }
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => 'Failed to update promotion', 'message' => $e->getMessage()], 500);
    }
}

// Format promotion data for API response
function formatPromotion($promo) {
    $formatted = [
        'id' => $promo['id'],
        'type' => $promo['type'],
        'title' => $promo['title'],
        'subtitle' => $promo['subtitle'],
        'description' => $promo['description'],
        'imageUrl' => $promo['image_url'],
        'linkUrl' => $promo['link_url'],
        'startDate' => $promo['start_date'],
        'endDate' => $promo['end_date'],
        'priority' => (int)$promo['priority'],
        'enabled' => (bool)$promo['enabled'],
        'createdAt' => $promo['created_at'],
        'updatedAt' => $promo['updated_at'],
        'createdBy' => $promo['created_by']
    ];
    
    // Add pricing if available
    if ($promo['special_price'] !== null || $promo['list_price'] !== null) {
        $formatted['pricing'] = [
            'specialPrice' => $promo['special_price'] ? (float)$promo['special_price'] : null,
            'listPrice' => $promo['list_price'] ? (float)$promo['list_price'] : null,
            'currency' => $promo['currency'],
            'displayFormat' => $promo['display_format']
        ];
    }
    
    // Add product details if available
    if ($promo['product_code'] || $promo['color'] || $promo['tablet'] || $promo['base_info'] || $promo['features']) {
        $formatted['productDetails'] = [
            'productCode' => $promo['product_code'],
            'color' => $promo['color'],
            'tablet' => $promo['tablet'],
            'base' => $promo['base_info'],
            'features' => $promo['features']
        ];
    }
    
    return $formatted;
}

// If no matching method, return error
sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
?>
