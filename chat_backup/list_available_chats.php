<?php
/**
 * RingCentral Chat List Utility
 * 
 * This script lists all available chats/teams in RingCentral to help find
 * a valid chat ID for use in the chat integration.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set content type to JSON for CLI friendliness or API-like responses
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

// Get chat type from URL parameter or default to 'Team'
$type = isset($_GET['type']) ? $_GET['type'] : null;
$format = isset($_GET['format']) ? $_GET['format'] : 'full';

// Initialize results array
$results = [
    'authenticated' => false,
    'chats' => [],
    'errors' => [],
    'warnings' => [],
    'info' => []
];

// Initialize the RingCentral client with JWT
$rcClient = new RingCentralTeamMessagingClient([
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'serverUrl' => RINGCENTRAL_SERVER,
    'jwtToken' => defined('RINGCENTRAL_JWT_TOKEN') ? RINGCENTRAL_JWT_TOKEN : '',
    'tokenPath' => __DIR__ . '/secure_storage/rc_token.json',
    'logFile' => __DIR__ . '/list_chats.log'
]);

// Try to authenticate
$authenticated = $rcClient->authenticate();
$results['authenticated'] = $authenticated;

if ($authenticated) {
    $results['info'][] = "Successfully authenticated with RingCentral";
    
    // List chats
    $chatsData = $rcClient->listChats($type);
    
    if ($chatsData && isset($chatsData['records'])) {
        $chats = $chatsData['records'];
        $results['info'][] = "Retrieved " . count($chats) . " chats";
        
        // Process each chat
        foreach ($chats as $chat) {
            $chatInfo = [
                'id' => $chat['id'] ?? 'unknown',
                'name' => $chat['name'] ?? 'Unnamed',
                'type' => $chat['type'] ?? 'unknown',
                'description' => $chat['description'] ?? '',
                'members' => isset($chat['members']) ? count($chat['members']) : 0
            ];
            
            if ($format === 'simple') {
                // Simple format just shows ID and name
                $results['chats'][] = [
                    'id' => $chatInfo['id'],
                    'name' => $chatInfo['name']
                ];
            } else {
                // Full format includes all details
                $results['chats'][] = $chatInfo;
            }
        }
        
        // Add a config helper
        if (count($results['chats']) > 0) {
            $firstChatId = $results['chats'][0]['id'] ?? '';
            if ($firstChatId) {
                $results['config_sample'] = "define('RINGCENTRAL_TEAM_CHAT_ID', '{$firstChatId}'); // Update in config.php";
            }
        }
    } else {
        $results['warnings'][] = "No chats found or error retrieving chats";
    }
} else {
    $results['errors'][] = "Failed to authenticate with RingCentral";
}

// Add test timestamp
$results['test_time'] = date('Y-m-d H:i:s');

// Output the results
echo json_encode($results, JSON_PRETTY_PRINT);

// If CLI, add newline
if (php_sapi_name() === 'cli') {
    echo PHP_EOL;
}
?>
