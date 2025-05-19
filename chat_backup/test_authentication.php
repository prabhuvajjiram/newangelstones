<?php
/**
 * Test Authentication Status
 * 
 * This script tests if the RingCentral authentication is working properly
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Whether this is CLI or web
$isCli = (php_sapi_name() === 'cli');

// If web request, set appropriate headers
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>RingCentral Authentication Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            h1 { color: #0067b8; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
            .success { color: green; font-weight: bold; }
            .error { color: red; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>RingCentral Authentication Test</h1>
        <pre>';
}

echo "RingCentral Authentication Test\n";
echo "==============================\n\n";

// Initialize client with credentials from config
$client = new RingCentralTeamMessagingClient([
    'serverUrl' => RINGCENTRAL_SERVER,
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN,
    'teamChatId' => RINGCENTRAL_DEFAULT_CHAT_ID ?? RINGCENTRAL_TEAM_CHAT_ID
]);

// Test authentication
echo "Testing authentication...\n";
try {
    if ($client->isAuthenticated()) {
        echo "✅ SUCCESS: Authentication is working properly\n";
        
        // Try to get access token
        $accessToken = $client->getAccessToken();
        if ($accessToken) {
            echo "✅ SUCCESS: Retrieved access token: " . substr($accessToken, 0, 10) . "...\n";
        } else {
            echo "❌ ERROR: Failed to retrieve access token\n";
        }
        
        // Try to list chats
        echo "\nTesting API call (listChats)...\n";
        $chats = $client->listChats('Team');
        if ($chats && isset($chats['records'])) {
            $count = count($chats['records']);
            echo "✅ SUCCESS: Retrieved $count team chats\n";
            
            // Show first chat details if any
            if ($count > 0) {
                $firstChat = $chats['records'][0];
                echo "  - Chat ID: " . ($firstChat['id'] ?? 'unknown') . "\n";
                echo "  - Name: " . ($firstChat['name'] ?? 'unknown') . "\n";
                echo "  - Type: " . ($firstChat['type'] ?? 'unknown') . "\n";
            }
        } else {
            echo "❌ ERROR: Failed to retrieve chats\n";
        }
    } else {
        echo "❌ ERROR: Authentication failed\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// If web request, close the HTML
if (!$isCli) {
    echo '</pre>
    </body>
    </html>';
}
?>
