<?php
/**
 * Test Message Sending
 * 
 * This script tests only the message sending functionality to RingCentral
 */

// Include required files
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create output format
function output($message, $type = 'info') {
    $prefix = '';
    switch($type) {
        case 'error': $prefix = '❌ '; break;
        case 'success': $prefix = '✅ '; break;
        case 'warning': $prefix = '⚠️ '; break;
        default: $prefix = 'ℹ️ '; break;
    }
    
    echo "{$prefix} {$message}\n";
}

// Log to file for debugging
function log_debug($message) {
    $logFile = __DIR__ . '/message_test.log';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "{$timestamp} {$message}\n", FILE_APPEND);
}

output("RingCentral Message Sending Test", 'info');
output("===============================", 'info');

try {
    // Check for required constants
    if (!defined('RINGCENTRAL_TEAM_CHAT_ID') && !defined('RINGCENTRAL_DEFAULT_CHAT_ID')) {
        throw new Exception("Neither RINGCENTRAL_TEAM_CHAT_ID nor RINGCENTRAL_DEFAULT_CHAT_ID is defined in config");
    }
    
    // Determine which chat ID to use
    $chatId = defined('RINGCENTRAL_DEFAULT_CHAT_ID') ? RINGCENTRAL_DEFAULT_CHAT_ID : RINGCENTRAL_TEAM_CHAT_ID;
    output("Using chat ID: " . $chatId);
    
    // Initialize the client
    output("Initializing RingCentral client...");
    $client = new RingCentralTeamMessagingClient([
        'serverUrl' => RINGCENTRAL_SERVER,
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'jwtToken' => RINGCENTRAL_JWT_TOKEN,
        'teamChatId' => $chatId,
        'enableDebug' => true
    ]);
    
    // Test authentication
    output("Testing authentication...");
    if (!$client->isAuthenticated()) {
        throw new Exception("Authentication failed");
    }
    output("Authentication successful", 'success');
    
    // Get access token
    $token = $client->getAccessToken();
    if (empty($token)) {
        throw new Exception("Failed to get access token");
    }
    output("Got access token: " . substr($token, 0, 10) . "...", 'success');
    
    // Try to list chats first to verify API connectivity
    output("\nListing team chats...");
    $chats = $client->listChats('Team');
    
    if (!isset($chats['records'])) {
        throw new Exception("Failed to list chats: " . print_r($chats, true));
    }
    
    $chatCount = count($chats['records']);
    output("Found {$chatCount} team chats", 'success');
    
    // Display the first few chats for selection
    output("\nAvailable team chats:");
    for ($i = 0; $i < min($chatCount, 5); $i++) {
        $chat = $chats['records'][$i];
        output("  {$i}: {$chat['name']} (ID: {$chat['id']})");
        
        // If this matches our default chat ID, note it
        if ($chat['id'] == $chatId) {
            output("  ⭐ This is your configured default chat");
        }
    }
    
    // Prepare test message
    $timestamp = date('Y-m-d H:i:s');
    $message = "Test message from diagnostic script at {$timestamp}";
    
    // Try to send message to configured chat
    output("\nSending test message to default chat (ID: {$chatId})...");
    log_debug("Attempting to send message: '{$message}' to chat ID: {$chatId}");
    
    $result = $client->postMessage($chatId, $message);
    
    // Check result
    if (isset($result['id'])) {
        output("Message sent successfully! Message ID: {$result['id']}", 'success');
        log_debug("Message sent successfully. Message ID: {$result['id']}");
    } else {
        output("Failed to send message. Response: " . print_r($result, true), 'error');
        log_debug("Failed to send message. Response: " . print_r($result, true));
        
        // Try with direct cURL request
        output("\nTrying direct API call...");
        $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/chats/' . $chatId . '/posts';
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-RingCentral-API-Group: medium'
        ]);
        
        $payload = json_encode(['text' => $message . ' (direct API call)']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 || $httpCode == 201) {
            $jsonResponse = json_decode($response, true);
            if (isset($jsonResponse['id'])) {
                output("Direct API call successful! Message ID: {$jsonResponse['id']}", 'success');
                log_debug("Direct API call successful. Message ID: {$jsonResponse['id']}");
            } else {
                output("Direct API call returned unexpected format", 'error');
                log_debug("Direct API call returned unexpected format: " . $response);
            }
        } else {
            output("Direct API call failed with HTTP code {$httpCode}", 'error');
            output("Response: {$response}", 'error');
            log_debug("Direct API call failed with HTTP code {$httpCode}. Response: {$response}");
        }
    }
    
} catch (Exception $e) {
    output("ERROR: " . $e->getMessage(), 'error');
    log_debug("Exception: " . $e->getMessage());
    
    // If there's a trace
    if ($e->getTraceAsString()) {
        log_debug("Trace: " . $e->getTraceAsString());
    }
}

output("\nTest completed. Check message_test.log for details.");
?>
