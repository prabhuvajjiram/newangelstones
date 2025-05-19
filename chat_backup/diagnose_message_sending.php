<?php
/**
 * RingCentral Message Sending Diagnostic
 * 
 * This script will diagnose issues with sending messages
 * and write results to a log file for reliable reference
 */

// Include required files
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Define log file
$logFile = __DIR__ . '/send_diagnostic.log';
file_put_contents($logFile, "RingCentral Message Sending Diagnostic\n");
file_put_contents($logFile, "Started at: " . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

// Logging function
function log_message($message) {
    global $logFile;
    file_put_contents($logFile, date('[H:i:s] ') . $message . "\n", FILE_APPEND);
    echo $message . "\n";
}

try {
    // Check for RingCentral configuration
    log_message("Checking RingCentral configuration...");
    
    if (!defined('RINGCENTRAL_SERVER')) {
        throw new Exception("RINGCENTRAL_SERVER not defined in config");
    }
    
    if (!defined('RINGCENTRAL_CLIENT_ID')) {
        throw new Exception("RINGCENTRAL_CLIENT_ID not defined in config");
    }
    
    if (!defined('RINGCENTRAL_CLIENT_SECRET')) {
        throw new Exception("RINGCENTRAL_CLIENT_SECRET not defined in config");
    }
    
    if (!defined('RINGCENTRAL_JWT_TOKEN')) {
        throw new Exception("RINGCENTRAL_JWT_TOKEN not defined in config");
    }
    
    // Determine which chat ID to use
    $chatId = null;
    if (defined('RINGCENTRAL_DEFAULT_CHAT_ID') && !empty(RINGCENTRAL_DEFAULT_CHAT_ID)) {
        $chatId = RINGCENTRAL_DEFAULT_CHAT_ID;
        log_message("Using RINGCENTRAL_DEFAULT_CHAT_ID: " . $chatId);
    } elseif (defined('RINGCENTRAL_TEAM_CHAT_ID') && !empty(RINGCENTRAL_TEAM_CHAT_ID)) {
        $chatId = RINGCENTRAL_TEAM_CHAT_ID;
        log_message("Using RINGCENTRAL_TEAM_CHAT_ID: " . $chatId);
    } else {
        throw new Exception("Neither RINGCENTRAL_DEFAULT_CHAT_ID nor RINGCENTRAL_TEAM_CHAT_ID defined in config");
    }
    
    log_message("Configuration check passed");
    
    // Step 1: Authenticate with RingCentral to get a token
    log_message("\nStep 1: Authenticating with RingCentral...");
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => RINGCENTRAL_JWT_TOKEN
    ];
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded',
        'X-RingCentral-API-Group: medium'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        throw new Exception("CURL Error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Parse response
    $tokenData = json_decode($response, true);
    if ($httpCode != 200 || !isset($tokenData['access_token'])) {
        log_message("Authentication failed with HTTP code " . $httpCode);
        log_message("Response: " . print_r($tokenData, true));
        throw new Exception("Failed to authenticate with RingCentral");
    }
    
    $accessToken = $tokenData['access_token'];
    log_message("Authentication successful");
    log_message("Access token: " . substr($accessToken, 0, 10) . "...");
    log_message("Token expires in: " . ($tokenData['expires_in'] ?? 'unknown') . " seconds");
    
    // Step 2: List chats to check API connectivity
    log_message("\nStep 2: Listing RingCentral chats...");
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/teams';
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
        'X-RingCentral-API-Group: medium'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        throw new Exception("CURL Error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Parse response
    $chatsData = json_decode($response, true);
    if ($httpCode != 200 || !isset($chatsData['records'])) {
        log_message("Failed to list chats with HTTP code " . $httpCode);
        log_message("Response: " . print_r($chatsData, true));
        throw new Exception("Failed to list RingCentral chats");
    }
    
    $chatCount = count($chatsData['records']);
    log_message("Successfully retrieved " . $chatCount . " chats");
    
    // Check if our chat ID exists in the list
    $chatExists = false;
    foreach ($chatsData['records'] as $chat) {
        if ($chat['id'] == $chatId) {
            log_message("Found configured chat ID in list: " . $chat['name']);
            $chatExists = true;
            break;
        }
    }
    
    if (!$chatExists) {
        log_message("WARNING: Configured chat ID " . $chatId . " not found in list of chats");
        log_message("Available chats:");
        
        foreach ($chatsData['records'] as $index => $chat) {
            log_message("  " . ($index + 1) . ". ID: " . $chat['id'] . ", Name: " . $chat['name']);
            
            // If we don't have a chat ID yet, use the first one
            if ($index === 0 && !$chatExists) {
                $chatId = $chat['id'];
                log_message("Using first available chat ID: " . $chatId);
            }
        }
    }
    
    // Step 3: Send a test message
    log_message("\nStep 3: Sending test message...");
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/chats/' . $chatId . '/posts';
    $timestamp = date('Y-m-d H:i:s');
    $messageText = "Test message from diagnostic script at " . $timestamp;
    
    $payload = json_encode(['text' => $messageText]);
    
    log_message("Endpoint: " . $endpoint);
    log_message("Message: " . $messageText);
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
        'X-RingCentral-API-Group: medium'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // Enable verbose output for detailed debugging
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Get verbose information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    
    if ($response === false) {
        log_message("CURL Error: " . curl_error($ch));
        log_message("Verbose log:\n" . $verboseLog);
        throw new Exception("Failed to send message");
    }
    
    curl_close($ch);
    
    // Parse response
    $messageData = json_decode($response, true);
    
    log_message("HTTP Code: " . $httpCode);
    log_message("Response: " . print_r($messageData, true));
    log_message("Verbose log:\n" . $verboseLog);
    
    if ($httpCode == 200 || $httpCode == 201) {
        if (isset($messageData['id'])) {
            log_message("Message sent successfully! Message ID: " . $messageData['id']);
        } else {
            log_message("Message appears to have been sent but no ID was returned");
        }
    } else {
        log_message("Failed to send message with HTTP code " . $httpCode);
    }
    
    // Step 4: Try to check the RingCentralTeamMessagingClient class
    log_message("\nStep 4: Checking for RingCentralTeamMessagingClient class...");
    
    if (file_exists(__DIR__ . '/RingCentralTeamMessagingClient.php')) {
        log_message("RingCentralTeamMessagingClient.php exists");
        
        // Check for any backups
        $backups = glob(__DIR__ . '/RingCentralTeamMessagingClient.php.bak.*');
        if (!empty($backups)) {
            log_message("Found " . count($backups) . " backup files:");
            foreach ($backups as $backup) {
                log_message("  " . basename($backup));
            }
        }
    } else {
        log_message("RingCentralTeamMessagingClient.php not found!");
    }
    
    log_message("\nDiagnostic completed successfully");
    
} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    
    if ($e->getTraceAsString()) {
        log_message("Stack trace: " . $e->getTraceAsString());
    }
    
    log_message("\nDiagnostic failed");
}

// Final message pointing to the log file
log_message("\nResults have been written to: " . $logFile);
echo "Results have been written to: " . $logFile . "\n";
?>
