<?php
/**
 * Test JWT Authentication
 * 
 * This script directly tests JWT authentication with RingCentral
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Create client
$client = new RingCentralTeamMessagingClient();

// Output for browser
header('Content-Type: text/html; charset=utf-8');
echo '<h1>RingCentral JWT Authentication Test</h1>';

try {
    // Test authentication
    echo '<h2>JWT Authentication Status</h2>';
    
    if ($client->isAuthenticated()) {
        echo '<p style="color: green; font-weight: bold;">✓ Successfully authenticated with JWT</p>';
        
        // Display token info
        $tokenInfo = $client->getJwtTokenStatus();
        echo '<pre>' . print_r($tokenInfo, true) . '</pre>';
        
        // Test posting a message
        echo '<h2>Test Message</h2>';
        
        try {
            $result = $client->postMessage(
                RINGCENTRAL_TEAM_CHAT_ID, 
                "Test message from JWT auth test at " . date('Y-m-d H:i:s')
            );
            
            echo '<p style="color: green; font-weight: bold;">✓ Successfully sent test message</p>';
            echo '<pre>' . print_r($result, true) . '</pre>';
        } catch (Exception $e) {
            echo '<p style="color: red; font-weight: bold;">Error sending message: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p style="color: red; font-weight: bold;">✗ Authentication failed</p>';
        echo '<p>Check your JWT token and credentials in config.php</p>';
        
        // Display validation errors
        $validationErrors = $client->getValidationErrors();
        if (!empty($validationErrors)) {
            echo '<h3>Validation Errors</h3>';
            echo '<pre>' . print_r($validationErrors, true) . '</pre>';
        }
    }
} catch (Exception $e) {
    echo '<p style="color: red; font-weight: bold;">Error: ' . $e->getMessage() . '</p>';
}

// Display configuration
echo '<h2>Configuration</h2>';
echo '<p><strong>JWT Token:</strong> ' . (defined('RINGCENTRAL_JWT_TOKEN') ? substr(RINGCENTRAL_JWT_TOKEN, 0, 20) . '...' : 'Not defined') . '</p>';
echo '<p><strong>Auth Type:</strong> ' . (defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'Not defined') . '</p>';
echo '<p><strong>Team Chat ID:</strong> ' . (defined('RINGCENTRAL_TEAM_CHAT_ID') ? RINGCENTRAL_TEAM_CHAT_ID : 'Not defined') . '</p>';
?>
