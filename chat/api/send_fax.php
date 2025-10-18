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
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
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

// Include fax API
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/../fax_api.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['to'])) {
        throw new Exception('Field "to" is required (recipient fax number)');
    }
    
    if (empty($input['files']) || !is_array($input['files'])) {
        throw new Exception('Field "files" is required (array of file objects)');
    }
    
    // Initialize fax client
    $faxClient = new RingCentralFaxClient([
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => defined('RINGCENTRAL_JWT_TOKEN') ? RINGCENTRAL_JWT_TOKEN : '',
        'authType' => defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'jwt',
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json'
    ]);
    
    // Prepare attachment data
    $attachmentData = [];
    
    foreach ($input['files'] as $file) {
        if (empty($file['name']) || empty($file['content'])) {
            throw new Exception('Each file must have "name" and "content" fields');
        }
        
        // Decode base64 content
        $content = base64_decode($file['content']);
        
        if ($content === false) {
            throw new Exception('Invalid base64 content in file: ' . $file['name']);
        }
        
        $attachmentData[] = [
            'name' => $file['name'],
            'content' => $content,
            'type' => $file['type'] ?? 'application/pdf'
        ];
    }
    
    // Prepare fax parameters
    $faxParams = [
        'to' => $input['to'],
        'attachment_data' => $attachmentData,
        'faxResolution' => $input['faxResolution'] ?? 'High'
    ];
    
    if (!empty($input['coverPageText'])) {
        $faxParams['coverPageText'] = $input['coverPageText'];
    }
    
    if (isset($input['coverIndex'])) {
        $faxParams['coverIndex'] = (int)$input['coverIndex'];
    }
    
    // Send fax
    $result = $faxClient->sendFax($faxParams);
    
    // Return result
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
