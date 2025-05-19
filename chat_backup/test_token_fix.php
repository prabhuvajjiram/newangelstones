<?php
/**
 * Test Token Storage Fix
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

echo "RingCentral Token Test\n";
echo "====================\n\n";

// Create client
$client = new RingCentralTeamMessagingClient([
    'serverUrl' => RINGCENTRAL_SERVER,
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN
]);

// Check authentication
echo "Testing authentication...\n";
if ($client->isAuthenticated()) {
    echo "✓ Authentication successful!\n";
    
    // Get access token
    $token = $client->getAccessToken();
    if ($token) {
        echo "✓ Access token retrieved: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "✗ Failed to get access token\n";
    }
    
    // Try to list chats
    echo "\nTesting API call (listChats)...\n";
    try {
        $chats = $client->listChats('Team');
        if ($chats && isset($chats['records'])) {
            $count = count($chats['records']);
            echo "✓ Retrieved $count team chats\n";
            
            if ($count > 0) {
                $firstChat = $chats['records'][0];
                echo "  - ID: " . ($firstChat['id'] ?? 'unknown') . "\n";
                echo "  - Name: " . ($firstChat['name'] ?? 'unknown') . "\n";
            }
        } else {
            echo "✗ No chats found or invalid response\n";
        }
    } catch (Exception $e) {
        echo "✗ API call error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Authentication failed\n";
}

echo "\nTest completed.\n";