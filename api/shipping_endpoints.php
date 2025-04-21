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
 */

// Enable error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
    
    // Define your API token - in production, use a more secure method
    $validToken = "AngelStones2025ApiToken"; // Replace with your secure token
    
    // In production, implement proper token validation
    // For development, accept any token of sufficient length
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return strlen($token) >= 10; 
    }
    
    // For production, do exact token matching
    return $token === $validToken;
}

// Create PDO connection to database
function getDbConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
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
            $query = "SELECT shipment_number FROM shipment_tracking ORDER BY shipment_number";
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
            
        default:
            // Invalid endpoint
            $responseHandler->sendResponse(404, ['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    // Handle any errors
    $responseHandler->sendResponse(500, ['error' => 'Server error', 'message' => $e->getMessage()]);
}
