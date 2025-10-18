<?php
/**
 * RingCentral Authentication Status Checker
 * 
 * Checks if we have a valid token for RingCentral API access
 * Supports both OAuth and JWT authentication
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set JSON header
header('Content-Type: application/json');

// Enable CORS
$allowedOrigins = [
    'https://angelgranites.com',
    'https://www.angelgranites.com',
    'http://localhost'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
}

// Determine authentication method
$authType = defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'oauth';

// If using JWT authentication
if ($authType === 'jwt') {
    // Initialize the RingCentral client with JWT
    $rcClient = new RingCentralTeamMessagingClient([
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => defined('RINGCENTRAL_JWT_TOKEN') ? RINGCENTRAL_JWT_TOKEN : '',
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json',
    ]);
    
    // Try to authenticate with JWT
    $authenticated = $rcClient->isAuthenticated();
    
    if ($authenticated) {
        // Get when the token expires
        $tokenPath = __DIR__ . '/secure_storage/rc_token.json';
        if (file_exists($tokenPath)) {
            $tokenData = json_decode(file_get_contents($tokenPath), true);
            $expiresAt = isset($tokenData['expires_at']) ? date('Y-m-d H:i:s', $tokenData['expires_at']) : 'unknown';
        } else {
            $expiresAt = 'unknown';
        }
        
        echo json_encode([
            'authenticated' => true,
            'auth_type' => 'jwt',
            'expires_at' => $expiresAt,
            'validity_status' => 'valid'
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'auth_type' => 'jwt',
            'message' => 'Failed to authenticate with RingCentral using JWT. Please check your JWT token.'
        ]);
    }
} else {
    // Original OAuth flow
    // Check if the token file exists
    $tokenPath = __DIR__ . '/secure_storage/rc_token.json';
    $oldTokenPath = __DIR__ . '/.ringcentral_token.json';
    
    // First check the secure storage location
    if (file_exists($tokenPath)) {
        $tokenData = json_decode(file_get_contents($tokenPath), true);
    } 
    // Fall back to the old location if needed
    else if (file_exists($oldTokenPath)) {
        $tokenData = json_decode(file_get_contents($oldTokenPath), true);
    } else {
        $tokenData = null;
    }
    
    // Check if token exists and is valid
    if ($tokenData && isset($tokenData['access_token']) && isset($tokenData['expires_at'])) {
        $isValid = $tokenData['expires_at'] > time();
        $expiresAt = date('Y-m-d H:i:s', $tokenData['expires_at']);
        
        echo json_encode([
            'authenticated' => $isValid,
            'auth_type' => 'oauth',
            'expires_at' => $expiresAt,
            'token_type' => $tokenData['token_type'] ?? 'bearer',
            'validity_status' => $isValid ? 'valid' : 'expired'
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'auth_type' => 'oauth',
            'message' => 'No authentication token found. Please authenticate with RingCentral.'
        ]);
    }
}
?>
