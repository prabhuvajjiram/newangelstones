<?php
/**
 * Shipping Tracking API Endpoints
 * 
 * This file provides REST API endpoints for accessing shipment tracking data
 * from the Angel Stones CRM system
 * 
 * Endpoints:
 * - GET /api/listShipments - List all shipment numbers
 * - GET /api/getShippingDetails/:id - Get detailed information about a specific shipment
 * - GET /api/getShippingDetailsV2/:id - Get a stable customer-safe shipment projection
 */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once dirname(__FILE__) . '/shipment_db_config.php';

/**
 * Response Handler Class
 * Formats and sends API responses
 */
class ResponseHandler {
    /**
     * Send API response
     * @param int $statusCode HTTP status code
     * @param array $data Response data
     */
    public function sendResponse($statusCode, $data) {
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Add timestamp to response
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        // Output JSON response
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
}

// Initialize response handler
$responseHandler = new ResponseHandler();

// Check if the Authorization header is set
function authorizeRequest() {
    // Get authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    // Simple token validation - in a real app, use a proper JWT/OAuth validation
    // For now just check if the token exists and has a valid format
    if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    
    // Preserve the existing documented integration token when the hosting
    // environment has not yet been configured. A server environment value
    // takes precedence so this fallback can be rotated without another code
    // deployment later.
    $validToken = getenv('SHIPPING_API_TOKEN') ?: 'AngelStones2025ApiToken';
    
    // In production, implement proper token validation
    // For development, accept any token of sufficient length
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return strlen($token) >= 10; 
    }
    
    return $validToken !== '' && hash_equals($validToken, $token);
}

// Create PDO connection to database
function getDbConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        error_log("Shipping API database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

function decodeJsonValue($value, $fallback = []) {
    if (is_array($value)) {
        return $value;
    }
    if (!is_string($value) || trim($value) === '') {
        return $fallback;
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function normalizeRoutesV2(array $routes) {
    return array_values(array_map(function ($route) {
        if (!is_array($route)) {
            return [];
        }
        // Rows saved before the July 2026 parser correction are shifted by
        // one column beginning at container. Normalize both shapes here.
        $legacyShifted = isset($route['vessel'])
            && preg_match('/^[A-Z]{4}[0-9]{7}$/i', trim((string) $route['vessel']));
        if ($legacyShifted) {
            return [
                'sequence' => $route['mode'] ?? null,
                'mode' => $route['type'] ?? null,
                'leg' => $route['parent'] ?? null,
                'billNumber' => $route['bill'] ?? null,
                'containerNumber' => $route['vessel'] ?? null,
                'vessel' => $route['voyage'] ?? null,
                'voyage' => $route['load_port'] ?? null,
                'origin' => $route['discharge_port'] ?? null,
                'destination' => $route['departure'] ?? null,
                'departure' => $route['arrival'] ?? null,
                'arrival' => $route['status'] ?? null,
                'status' => $route['carrier'] ?? null,
            ];
        }
        return [
            'sequence' => $route['mode'] ?? null,
            'mode' => $route['type'] ?? null,
            'leg' => $route['parent'] ?? null,
            'billNumber' => $route['bill'] ?? null,
            'containerNumber' => $route['container'] ?? null,
            'vessel' => $route['vessel'] ?? null,
            'voyage' => $route['voyage'] ?? null,
            'origin' => $route['load_port'] ?? null,
            'destination' => $route['discharge_port'] ?? null,
            'departure' => $route['departure'] ?? null,
            'arrival' => $route['arrival'] ?? null,
            'status' => $route['status'] ?? null,
        ];
    }, $routes));
}

/**
 * Versioned, customer-safe contract. Keep getShippingDetails unchanged for
 * existing consumers while new ERP clients use this stable projection.
 */
function normalizeShipmentV2(array $shipment) {
    $fullData = decodeJsonValue($shipment['full_data'] ?? null, []);
    $routes = normalizeRoutesV2(decodeJsonValue(
        $shipment['container_routes_json'] ?? ($fullData['container_routes_json'] ?? null),
        []
    ));
    $containers = decodeJsonValue($fullData['container_details'] ?? null, []);
    if (empty($containers) && !empty($shipment['containers'])) {
        $containers = array_values(array_filter(array_map(function ($number) {
            $trimmed = trim($number);
            return $trimmed === '' ? null : ['containerNumber' => $trimmed];
        }, preg_split('/[,;\s]+/', $shipment['containers']))));
    }
    $latestRoute = empty($routes) ? [] : $routes[count($routes) - 1];

    return [
        'shipmentNumber' => $shipment['shipment_number'] ?? null,
        'billNumber' => $shipment['bill'] ?? ($shipment['bill_number'] ?? null),
        'status' => $shipment['status'] ?? ($shipment['current_status'] ?? ($latestRoute['status'] ?? null)),
        'origin' => $shipment['origin'] ?? ($shipment['current_load_port'] ?? null),
        'destination' => $shipment['destination'] ?? ($shipment['current_discharge_port'] ?? null),
        'etd' => $shipment['etd'] ?? ($shipment['estimated_departure'] ?? null),
        'eta' => $shipment['eta'] ?? ($shipment['estimated_arrival'] ?? null),
        'vessel' => $shipment['current_vessel'] ?? ($shipment['main_vessel'] ?? null),
        'voyage' => $shipment['current_voyage'] ?? ($shipment['main_voyage'] ?? null),
        'containerMode' => $shipment['container_mode'] ?? null,
        'lastUpdatedAt' => $shipment['last_updated'] ?? null,
        'route' => $routes,
        'containers' => $containers,
    ];
}

// Get endpoint from query parameter
$endpoint = $_GET['endpoint'] ?? '';
$parameter = $_GET['id'] ?? null;

// Check if API endpoint is valid
if (empty($endpoint)) {
    $responseHandler->sendResponse(404, ['error' => 'API endpoint not found']);
}

// Handle all endpoints
try {
    // Authorize request first (except for OPTIONS preflight)
    if (!authorizeRequest()) {
        $responseHandler->sendResponse(401, ['error' => 'Unauthorized access. Valid authorization token required.']);
    }
    
    // Database connection
    $db = getDbConnection();
    
    // Handle endpoints
    switch ($endpoint) {
        case 'listShipments':
            // Handle GET request to list all shipment numbers
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $responseHandler->sendResponse(405, ['error' => 'Method not allowed']);
            }
            
            // Prepare SQL query
            $query = "SELECT shipment_number FROM shipment_tracking
                      WHERE shipment_number NOT LIKE 'Pages:%'
                        AND shipment_number NOT LIKE 'Found %'
                      ORDER BY shipment_number";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            // Get row count
            $numRows = $stmt->rowCount();
            
            // Check if any records found
            if ($numRows > 0) {
                // Initialize array for data
                $shipmentData = [];
                $shipmentData['count'] = $numRows;
                $shipmentData['shipments'] = [];
                
                // Get all records
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($shipmentData['shipments'], $row['shipment_number']);
                }
                
                // Send successful response
                $responseHandler->sendResponse(200, $shipmentData);
            } else {
                // No records found
                $responseHandler->sendResponse(200, ['count' => 0, 'shipments' => []]);
            }
            break;
            
        case 'getShippingDetails':
            // Handle GET request to get shipping details by ID
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $responseHandler->sendResponse(405, ['error' => 'Method not allowed']);
            }
            
            // Check if ID parameter is provided
            if (empty($parameter)) {
                $responseHandler->sendResponse(400, ['error' => 'Shipment ID is required']);
            }
            
            // Prepare SQL query
            $query = "SELECT * FROM shipment_tracking WHERE shipment_number = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $parameter);
            $stmt->execute();
            
            // Check if any records found
            if ($stmt->rowCount() > 0) {
                // Fetch the record
                $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Send successful response
                $responseHandler->sendResponse(200, ['shipment' => $shipment]);
            } else {
                // No records found
                $responseHandler->sendResponse(404, ['error' => 'Shipment not found']);
            }
            break;

        case 'getShippingDetailsV2':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $responseHandler->sendResponse(405, ['error' => 'Method not allowed']);
            }
            if (empty($parameter)) {
                $responseHandler->sendResponse(400, [
                    'error' => 'Bad Request',
                    'message' => 'Shipment ID is required'
                ]);
            }

            $query = "SELECT * FROM shipment_tracking WHERE shipment_number = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $parameter);
            $stmt->execute();
            $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shipment) {
                $responseHandler->sendResponse(404, [
                    'error' => 'Not Found',
                    'message' => 'Shipment not found'
                ]);
            }
            $responseHandler->sendResponse(200, [
                'apiVersion' => '2',
                'shipment' => normalizeShipmentV2($shipment)
            ]);
            break;
            
        default:
            // Invalid endpoint
            $responseHandler->sendResponse(404, ['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    // Handle any errors
    error_log('Shipping API error: ' . $e->getMessage());
    $responseHandler->sendResponse(500, ['error' => 'Server error']);
}
