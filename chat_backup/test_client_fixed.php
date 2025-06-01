<?php
/**
 * Test RingCentralTeamMessagingClient Class
 * This verifies the client class is working correctly after our fixes
 */

// Include necessary files
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

echo "RingCentralTeamMessagingClient Test\n";
echo "================================\n\n";

// Create client
$client = new RingCentralTeamMessagingClient([
    'serverUrl' => RINGCENTRAL_SERVER,
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN,
    'enableDebug' => true
]);

// Test authentication
echo "Testing authentication...\n";
if ($client->isAuthenticated()) {
    echo "✅ Authentication successful!\n";
    
    // Get access token
    $token = $client->getAccessToken();
    echo "✅ Access token: " . substr($token, 0, 10) . "...\n\n";
    
    // Test API calls
    echo "Testing API calls:\n";
    
    // 1. List chats
    echo "- Listing chats...\n";
    $chats = $client->listChats('Team');
    if (isset($chats['records'])) {
        $count = count($chats['records']);
        echo "  ✅ Retrieved $count chats\n";
        
        // Get the team chat ID 
        if ($count > 0) {
            $chatId = $chats['records'][0]['id'];
            echo "  ✅ First chat ID: $chatId\n";
            
            // 2. Post a test message
            echo "\n- Posting test message...\n";
            $message = "Test message from fixed RingCentralTeamMessagingClient class at " . date('Y-m-d H:i:s');
            $result = $client->postMessage($chatId, $message);
            if ($result && isset($result['id'])) {
                echo "  ✅ Message posted successfully (ID: " . $result['id'] . ")\n";
            } else {
                echo "  ❌ Failed to post message\n";
            }
        }
    } else {
        echo "  ❌ Failed to retrieve chats\n";
    }
} else {
    echo "❌ Authentication failed\n";
}

echo "\nTest completed.\n";
?>
