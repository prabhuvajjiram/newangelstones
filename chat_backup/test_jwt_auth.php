<?php
/**
 * RingCentral JWT Authentication Test Script
 * 
 * This script tests the JWT authentication with RingCentral and verifies
 * that the system can successfully authenticate and send messages.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set content type to JSON for API-like responses
header('Content-Type: application/json');

// Define test results
$results = [
    'jwt_configured' => false,
    'jwt_auth_success' => false,
    'test_message_sent' => false,
    'errors' => [],
    'warnings' => [],
    'info' => []
];

// Verify JWT configuration
if (defined('RINGCENTRAL_AUTH_TYPE') && RINGCENTRAL_AUTH_TYPE === 'jwt') {
    $results['jwt_configured'] = true;
    $results['info'][] = "JWT authentication is configured in config.php";
} else {
    $results['errors'][] = "JWT authentication is not configured. Check RINGCENTRAL_AUTH_TYPE in config.php";
}

if (defined('RINGCENTRAL_JWT_TOKEN') && !empty(RINGCENTRAL_JWT_TOKEN)) {
    $results['info'][] = "JWT token is defined in config.php";
} else {
    $results['errors'][] = "JWT token is not defined or is empty. Check RINGCENTRAL_JWT_TOKEN in config.php";
}

// Only continue if JWT is configured
if ($results['jwt_configured'] && empty($results['errors'])) {
    // Initialize the RingCentral client
    $rcClient = new RingCentralTeamMessagingClient([
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => RINGCENTRAL_JWT_TOKEN,
        'teamChatId' => RINGCENTRAL_TEAM_CHAT_ID,
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json',
        'logFile' => __DIR__ . '/jwt_test.log'
    ]);
    
    // Try to authenticate
    $authenticated = $rcClient->authenticate();
    
    if ($authenticated) {
        $results['jwt_auth_success'] = true;
        $results['info'][] = "Successfully authenticated with RingCentral using JWT";
        
        // Check if we have a team chat ID configured
        if (defined('RINGCENTRAL_TEAM_CHAT_ID') && !empty(RINGCENTRAL_TEAM_CHAT_ID)) {
            // Try to post a test message
            $testMessage = "**JWT Authentication Test**\n\n" .
                           "This is a test message sent at " . date('Y-m-d H:i:s') . " to verify JWT authentication is working properly.\n\n" .
                           "_This message was sent automatically by the test_jwt_auth.php script._";
            
            $messageResult = $rcClient->postMessage(RINGCENTRAL_TEAM_CHAT_ID, $testMessage);
            
            if ($messageResult && isset($messageResult['id'])) {
                $results['test_message_sent'] = true;
                $results['info'][] = "Successfully sent test message to RingCentral chat";
                $results['message_id'] = $messageResult['id'];
            } else {
                $results['warnings'][] = "Failed to send test message to RingCentral chat";
            }
        } else {
            $results['warnings'][] = "RINGCENTRAL_TEAM_CHAT_ID is not defined or is empty. Cannot send test message.";
        }
    } else {
        $results['errors'][] = "Failed to authenticate with RingCentral using JWT";
    }
}

// Get the token information if available
if (file_exists(__DIR__ . '/secure_storage/rc_token.json')) {
    $tokenData = json_decode(file_get_contents(__DIR__ . '/secure_storage/rc_token.json'), true);
    if ($tokenData) {
        $results['token_info'] = [
            'expires_at' => isset($tokenData['expires_at']) ? date('Y-m-d H:i:s', $tokenData['expires_at']) : 'unknown',
            'token_type' => $tokenData['token_type'] ?? 'unknown',
            'scope' => $tokenData['scope'] ?? 'unknown'
        ];
    }
}

// Calculate overall success
$results['success'] = empty($results['errors']) && $results['jwt_auth_success'];

// Add test timestamp
$results['test_time'] = date('Y-m-d H:i:s');

// Output the results
echo json_encode($results, JSON_PRETTY_PRINT);
?>
