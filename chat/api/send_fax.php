<?php
/**
 * RingCentral Fax API - Simple JSON Endpoint
 * 
 * Simplified endpoint for sending faxes via JSON requests
 * Accepts base64-encoded file content
 */

header('Content-Type: application/json');

// Enable CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowedOrigins = [
        'https://theangelstones.com',
        'https://www.theangelstones.com',
        'http://localhost',
        'http://localhost:3000'
    ];
    
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    }
}

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Include shared modules
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/../RingCentralFaxClient.php';
require_once __DIR__ . '/../fax_validation.php';
require_once __DIR__ . '/../fax_security.php';
require_once __DIR__ . '/../config.php';

// Initialize security
$securityEnabled = file_exists(__DIR__ . '/../fax_security_config.php');
if ($securityEnabled) {
    $security = new FaxSecurityManager(__DIR__ . '/../fax_security_config.php');
    $security->authorize(); // Will exit if authorization fails
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        throw new Exception('Invalid JSON input');
    }
    
    // Validate fax parameters using shared validator
    $validation = FaxValidator::validateFaxParams($input);
    
    if (!$validation['valid']) {
        http_response_code(400);
        throw new Exception(implode(', ', $validation['errors']));
    }
    
    $sanitized = $validation['params'];
    
    // Validate and process base64 files
    if (empty($input['files']) || !is_array($input['files'])) {
        http_response_code(400);
        throw new Exception('Field "files" is required (array of file objects)');
    }
    
    $filesResult = FaxValidator::processBase64Files($input['files']);
    
    if (!$filesResult['valid']) {
        http_response_code(413); // Payload Too Large if file size issue
        throw new Exception($filesResult['error']);
    }
    
    // Initialize fax client
    $faxClient = new RingCentralFaxClient([
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => defined('RINGCENTRAL_JWT_TOKEN') ? RINGCENTRAL_JWT_TOKEN : '',
        'authType' => defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'jwt',
        'tokenPath' => __DIR__ . '/../secure_storage/rc_token.json'
    ]);
    
    // Prepare fax parameters
    $faxParams = [
        'to' => $sanitized['to'],
        'attachment_data' => $filesResult['files'],
        'faxResolution' => $sanitized['faxResolution'] ?? 'High'
    ];
    
    // Add optional parameters if provided
    if (!empty($sanitized['coverPageText'])) {
        $faxParams['coverPageText'] = $sanitized['coverPageText'];
    }
    
    if (isset($input['coverIndex'])) {
        $faxParams['coverIndex'] = $input['coverIndex']; // Keep as string to allow 'custom'
    }
    
    // Add custom cover page parameters
    $customFields = ['to_name', 'to_company', 'from_name'];
    foreach ($customFields as $field) {
        if (isset($sanitized[$field])) {
            $faxParams[$field] = $sanitized[$field];
        }
    }
    
    if (isset($input['urgent'])) {
        $faxParams['urgent'] = (bool)$input['urgent'];
    }
    if (isset($input['confidential'])) {
        $faxParams['confidential'] = (bool)$input['confidential'];
    }
    
    // Send fax
    $result = $faxClient->sendFax($faxParams);
    
    // Return result
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    // Return appropriate HTTP status code if not already set
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
