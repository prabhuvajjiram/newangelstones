<?php
/**
 * Production Diagnostics for RingCentral Chat Integration
 * 
 * Upload this file to your production server and run it to diagnose issues
 * Access at: https://theangelstones.com/chat/production_diagnostics.php
 */

// Set up error reporting for diagnostics
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define log file
$logFile = __DIR__ . '/production_diagnostics.log';
file_put_contents($logFile, "RingCentral Production Diagnostics\n");
file_put_contents($logFile, "Started at: " . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

// Basic HTML output
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RingCentral Production Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #0067b8; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>RingCentral Production Diagnostics</h1>
    <p>This tool will diagnose issues with the RingCentral chat integration in production.</p>';

// Function to log and output
function log_output($message, $type = 'info') {
    global $logFile;
    
    // Add to log file
    file_put_contents($logFile, date('[H:i:s] ') . $message . "\n", FILE_APPEND);
    
    // Output to browser with styling
    $class = ($type == 'info') ? 'info' : $type;
    echo "<p class=\"{$class}\">{$message}</p>";
}

/**
 * Step 1: Check server environment
 */
echo '<h2>1. Server Environment</h2>';

// Check PHP version
$phpVersion = phpversion();
log_output("PHP Version: {$phpVersion}");

// Check server info
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
log_output("Server Software: {$serverSoftware}");

// Check if we're on production
$isProduction = (strpos($_SERVER['HTTP_HOST'] ?? '', 'theangelstones.com') !== false);
log_output("Environment: " . ($isProduction ? 'Production' : 'Non-Production'));

// Check extensions
$requiredExtensions = ['curl', 'json', 'pdo', 'pdo_mysql'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    log_output("Extension {$ext}: " . ($loaded ? 'Loaded' : 'NOT LOADED'), $loaded ? 'success' : 'error');
}

/**
 * Step 2: Check file paths and permissions
 */
echo '<h2>2. File Paths & Permissions</h2>';

// Check current directory
$currentDir = __DIR__;
log_output("Current directory: {$currentDir}");

// Check key files
$keyFiles = [
    'config.php',
    'RingCentralTeamMessagingClient.php',
    'api/send_message.php',
    'api/poll_messages.php',
    'api/webhook.php'
];

foreach ($keyFiles as $file) {
    $fullPath = $currentDir . '/' . $file;
    if (file_exists($fullPath)) {
        $readable = is_readable($fullPath);
        $writable = is_writable($fullPath);
        $modified = date('Y-m-d H:i:s', filemtime($fullPath));
        
        log_output("{$file}: EXISTS (Modified: {$modified}, Read: " . ($readable ? 'Yes' : 'No') . ", Write: " . ($writable ? 'Yes' : 'No') . ")", 'success');
    } else {
        log_output("{$file}: MISSING", 'error');
    }
}

// Check secure_storage directory
$securePath = $currentDir . '/secure_storage';
if (is_dir($securePath)) {
    $writable = is_writable($securePath);
    log_output("secure_storage directory: EXISTS (Writable: " . ($writable ? 'Yes' : 'No') . ")", $writable ? 'success' : 'warning');
    
    // Check files in secure_storage
    $tokenFiles = glob($securePath . '/*.json');
    if (count($tokenFiles) > 0) {
        log_output("Found " . count($tokenFiles) . " token files in secure_storage");
        foreach ($tokenFiles as $tokenFile) {
            $fileName = basename($tokenFile);
            $modified = date('Y-m-d H:i:s', filemtime($tokenFile));
            $size = filesize($tokenFile);
            log_output("- {$fileName} (Modified: {$modified}, Size: {$size} bytes)");
        }
    } else {
        log_output("No token files found in secure_storage", 'warning');
    }
} else {
    log_output("secure_storage directory: MISSING", 'error');
    
    // Try to create it
    if (mkdir($securePath, 0755, true)) {
        log_output("Created secure_storage directory", 'success');
    } else {
        log_output("Failed to create secure_storage directory", 'error');
    }
}

// Check root directory for token files
$rootTokenFiles = glob($currentDir . '/.ringcentral_token.json');
if (count($rootTokenFiles) > 0) {
    foreach ($rootTokenFiles as $tokenFile) {
        $fileName = basename($tokenFile);
        $modified = date('Y-m-d H:i:s', filemtime($tokenFile));
        $size = filesize($tokenFile);
        log_output("Found root token file: {$fileName} (Modified: {$modified}, Size: {$size} bytes)");
    }
} else {
    log_output("No token file found in root directory", 'warning');
}

/**
 * Step 3: Check configuration
 */
echo '<h2>3. Configuration</h2>';
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Check RingCentral config
if (defined('RINGCENTRAL_CLIENT_ID')) {
    log_output("RINGCENTRAL_CLIENT_ID: " . (empty(RINGCENTRAL_CLIENT_ID) ? 'Empty' : 'Set'), empty(RINGCENTRAL_CLIENT_ID) ? 'warning' : 'success');
} else {
    log_output("RINGCENTRAL_CLIENT_ID: Not defined", 'error');
}

if (defined('RINGCENTRAL_CLIENT_SECRET')) {
    log_output("RINGCENTRAL_CLIENT_SECRET: " . (empty(RINGCENTRAL_CLIENT_SECRET) ? 'Empty' : 'Set'), empty(RINGCENTRAL_CLIENT_SECRET) ? 'warning' : 'success');
} else {
    log_output("RINGCENTRAL_CLIENT_SECRET: Not defined", 'error');
}

if (defined('RINGCENTRAL_SERVER')) {
    log_output("RINGCENTRAL_SERVER: " . RINGCENTRAL_SERVER);
} else {
    log_output("RINGCENTRAL_SERVER: Not defined", 'error');
}

if (defined('RINGCENTRAL_DEFAULT_CHAT_ID')) {
    log_output("RINGCENTRAL_DEFAULT_CHAT_ID: " . RINGCENTRAL_DEFAULT_CHAT_ID);
} else {
    log_output("RINGCENTRAL_DEFAULT_CHAT_ID: Not defined", 'error');
}

if (defined('RINGCENTRAL_JWT_TOKEN')) {
    $token = RINGCENTRAL_JWT_TOKEN;
    $tokenPreview = substr($token, 0, 20) . '...' . substr($token, -5);
    log_output("RINGCENTRAL_JWT_TOKEN: " . $tokenPreview);
    
    // Parse JWT to check expiration
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        // Decode payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        
        if (isset($payload['exp'])) {
            $expiryTime = $payload['exp'];
            $currentTime = time();
            
            if ($expiryTime < $currentTime) {
                log_output("JWT Token is EXPIRED! Expired at: " . date('Y-m-d H:i:s', $expiryTime), 'error');
            } else {
                log_output("JWT Token is VALID! Expires at: " . date('Y-m-d H:i:s', $expiryTime), 'success');
                log_output("Token will expire in " . ($expiryTime - $currentTime) . " seconds (" . 
                         round(($expiryTime - $currentTime) / (3600*24), 1) . " days)");
            }
        }
    }
} else {
    log_output("RINGCENTRAL_JWT_TOKEN: Not defined", 'error');
}

/**
 * Step 4: Test Authentication and API Access
 */
echo '<h2>4. Authentication Test</h2>';

try {
    require_once __DIR__ . '/RingCentralTeamMessagingClient.php';
    
    // Initialize client
    log_output("Initializing RingCentralTeamMessagingClient...");
    $client = new RingCentralTeamMessagingClient([
        'serverUrl' => RINGCENTRAL_SERVER,
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'jwtToken' => RINGCENTRAL_JWT_TOKEN,
        'tokenPath' => __DIR__ . '/secure_storage/prod_token.json'
    ]);
    
    // Check if isAuthenticated method exists
    if (method_exists($client, 'isAuthenticated')) {
        log_output("Found isAuthenticated method in client class", 'success');
        
        // Test authentication
        log_output("Testing authentication...");
        if ($client->isAuthenticated()) {
            log_output("Authentication successful!", 'success');
            
            // Get access token
            $token = $client->getAccessToken();
            log_output("Access token: " . (empty($token) ? 'Empty' : substr($token, 0, 10) . '...'), empty($token) ? 'error' : 'success');
            
            // List chats
            log_output("Testing list chats API...");
            $chats = $client->listChats('Team');
            
            if (isset($chats['records'])) {
                $count = count($chats['records']);
                log_output("Successfully retrieved {$count} chats", 'success');
                
                // Display chats
                echo "<pre>";
                foreach ($chats['records'] as $index => $chat) {
                    echo ($index + 1) . ". ID: " . $chat['id'] . ", Name: " . ($chat['name'] ?? 'Unnamed') . "\n";
                    
                    // Check if this is the configured chat
                    if ($chat['id'] == RINGCENTRAL_DEFAULT_CHAT_ID) {
                        echo "   *** This is your configured default chat ***\n";
                    }
                }
                echo "</pre>";
                
                // Check if configured chat ID is valid
                $chatIdExists = false;
                foreach ($chats['records'] as $chat) {
                    if ($chat['id'] == RINGCENTRAL_DEFAULT_CHAT_ID) {
                        $chatIdExists = true;
                        break;
                    }
                }
                
                if ($chatIdExists) {
                    log_output("Your configured chat ID (" . RINGCENTRAL_DEFAULT_CHAT_ID . ") exists in the list of chats", 'success');
                } else {
                    log_output("Your configured chat ID (" . RINGCENTRAL_DEFAULT_CHAT_ID . ") was NOT found in the list of chats", 'error');
                    log_output("Please update your config.php with one of the chat IDs listed above");
                }
                
                // Test sending a message
                log_output("Testing message sending...");
                $message = "Test message from production diagnostics at " . date('Y-m-d H:i:s');
                $chatId = RINGCENTRAL_DEFAULT_CHAT_ID;
                
                $result = $client->postMessage($chatId, $message);
                
                if (isset($result['id'])) {
                    log_output("Message sent successfully! Message ID: " . $result['id'], 'success');
                } else {
                    log_output("Failed to send message. Response: " . print_r($result, true), 'error');
                    
                    // Try with direct API call
                    log_output("Trying direct API call...");
                    
                    // Get token directly from client object
                    $reflectionClass = new ReflectionClass($client);
                    $tokenProperty = $reflectionClass->getProperty('accessToken');
                    $tokenProperty->setAccessible(true);
                    $directToken = $tokenProperty->getValue($client);
                    
                    // Make direct API call
                    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/chats/' . $chatId . '/posts';
                    
                    $ch = curl_init($endpoint);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $directToken,
                        'Content-Type: application/json',
                        'X-RingCentral-API-Group: medium'
                    ]);
                    
                    $payload = json_encode(['text' => $message . ' (direct API call)']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    
                    // Verbose logging
                    $verbose = fopen('php://temp', 'w+');
                    curl_setopt($ch, CURLOPT_VERBOSE, true);
                    curl_setopt($ch, CURLOPT_STDERR, $verbose);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    // Get verbose log
                    rewind($verbose);
                    $verboseLog = stream_get_contents($verbose);
                    fclose($verbose);
                    
                    curl_close($ch);
                    
                    if ($httpCode == 200 || $httpCode == 201) {
                        $jsonResponse = json_decode($response, true);
                        if (isset($jsonResponse['id'])) {
                            log_output("Direct API call successful! Message ID: " . $jsonResponse['id'], 'success');
                        } else {
                            log_output("Direct API call returned unexpected format: " . $response, 'error');
                        }
                    } else {
                        log_output("Direct API call failed with HTTP code " . $httpCode, 'error');
                        log_output("Response: " . $response, 'error');
                        log_output("CURL verbose log:", 'info');
                        echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
                    }
                }
            } else {
                log_output("Failed to retrieve chats. Response: " . print_r($chats, true), 'error');
            }
        } else {
            log_output("Authentication failed", 'error');
            
            // Try to get last error
            $reflectionClass = new ReflectionClass($client);
            $lastErrorProperty = $reflectionClass->getProperty('lastError');
            $lastErrorProperty->setAccessible(true);
            $lastError = $lastErrorProperty->getValue($client);
            
            if (!empty($lastError)) {
                log_output("Last error: " . $lastError, 'error');
            }
            
            // Try direct authentication
            log_output("Trying direct authentication with JWT...");
            
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
            
            // Verbose logging
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Get verbose log
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            
            curl_close($ch);
            
            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);
                if (isset($jsonResponse['access_token'])) {
                    log_output("Direct authentication successful!", 'success');
                    log_output("Access token: " . substr($jsonResponse['access_token'], 0, 10) . "...", 'success');
                    
                    // Save token
                    $tokenData = [
                        'access_token' => $jsonResponse['access_token'],
                        'refresh_token' => $jsonResponse['refresh_token'] ?? '',
                        'expires_at' => time() + ($jsonResponse['expires_in'] ?? 3600),
                        'token_type' => $jsonResponse['token_type'] ?? 'bearer'
                    ];
                    
                    $tokenPath = __DIR__ . '/secure_storage/direct_token.json';
                    if (file_put_contents($tokenPath, json_encode($tokenData, JSON_PRETTY_PRINT))) {
                        log_output("Saved token to: " . $tokenPath, 'success');
                        
                        // Also save to root
                        $rootTokenPath = __DIR__ . '/.ringcentral_token.json';
                        if (file_put_contents($rootTokenPath, json_encode($tokenData, JSON_PRETTY_PRINT))) {
                            log_output("Also saved token to: " . $rootTokenPath, 'success');
                        }
                    }
                } else {
                    log_output("Direct authentication returned unexpected format", 'error');
                }
            } else {
                log_output("Direct authentication failed with HTTP code " . $httpCode, 'error');
                log_output("Response: " . $response, 'error');
                log_output("CURL verbose log:", 'info');
                echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
            }
        }
    } else {
        log_output("isAuthenticated method NOT found in client class - update needed!", 'error');
    }
} catch (Throwable $e) {
    log_output("Exception: " . $e->getMessage(), 'error');
    
    if ($e->getTraceAsString()) {
        log_output("Stack trace:", 'error');
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}

// Final output
echo '
    <h2>Diagnostic Summary</h2>
    <p>This page has performed a series of tests on your RingCentral chat integration.</p>
    <p>All results have been logged to: <code>' . htmlspecialchars($logFile) . '</code></p>
    <p><strong>Next Steps:</strong></p>
    <ol>
        <li>Check for any red error messages above</li>
        <li>Verify your configuration matches what we fixed in the local environment</li>
        <li>Make sure the RingCentralTeamMessagingClient.php file has been updated</li>
        <li>Ensure the secure_storage directory exists and is writable</li>
    </ol>
</body>
</html>';
?>
