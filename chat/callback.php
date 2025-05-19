<?php
/**
 * RingCentral OAuth Callback Handler
 * This file handles the callback from RingCentral OAuth authentication flow
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true); // Required to load config
require_once __DIR__ . '/config.php';

// Include RingCentral SDK
require_once __DIR__ . '/vendor/autoload.php';

// Define log file and logging function
$logFile = __DIR__ . '/ringcentral_chat.log';

function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

try {
    // Dump all request data for debugging
    logMessage('=== CALLBACK RECEIVED ===');
    logMessage('Request method: ' . $_SERVER['REQUEST_METHOD']);
    logMessage('Query string: ' . $_SERVER['QUERY_STRING']);
    
    // Get authorization code from the callback
    $code = isset($_GET['code']) ? $_GET['code'] : null;
    $state = isset($_GET['state']) ? $_GET['state'] : null;
    
    logMessage('Code: ' . ($code ? substr($code, 0, 20) . '...' : 'NULL'));
    logMessage('State: ' . ($state ?: 'NULL'));
    
    if (!$code) {
        throw new Exception('No authorization code received from RingCentral');
    }
    
    // Log the received code
    logMessage('Received authorization code: ' . substr($code, 0, 20) . '...');
    
    // Set token file path in a secure storage directory
    $tokenPath = __DIR__ . '/secure_storage/rc_token.json';
    
    // Make sure secure_storage directory exists
    if (!is_dir(__DIR__ . '/secure_storage')) {
        mkdir(__DIR__ . '/secure_storage', 0755, true);
        logMessage("Created secure_storage directory");
    }
    
    // Log token path
    logMessage('Setting token path to: ' . $tokenPath);
    
    // Log details about the OAuth exchange attempt
    logMessage('Using redirect URI: ' . RINGCENTRAL_REDIRECT_URI);
    logMessage('Using client ID: ' . substr(RINGCENTRAL_CLIENT_ID, 0, 5) . '...');
    logMessage('Using server URL: ' . RINGCENTRAL_SERVER);
    
    // Exchange authorization code for access token
    try {
        logMessage('Attempting to exchange code for token using direct curl...');
        
        // Use direct curl to exchange code for token
        $ch = curl_init(RINGCENTRAL_TOKEN_URL);
        $postFields = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => RINGCENTRAL_REDIRECT_URI
        ]);
        
        logMessage('POST fields: ' . $postFields);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_SSL_VERIFYPEER => false, // For local development
            CURLOPT_SSL_VERIFYHOST => 0,     // For local development
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            logMessage("CURL ERROR ($errno): $error", 'ERROR');
            throw new Exception("Connection error when exchanging code for token: $error");
        }
        
        curl_close($ch);
        logMessage('Token exchange HTTP code: ' . $httpCode);
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            if (!$tokenData || !isset($tokenData['access_token'])) {
                logMessage('Invalid token response: ' . substr($response, 0, 100), 'ERROR');
                throw new Exception('Invalid token response from RingCentral');
            }
            
            // Add expires_at fields
            $tokenData['expires_at'] = time() + $tokenData['expires_in'];
            if (isset($tokenData['refresh_token_expires_in'])) {
                $tokenData['refresh_token_expires_at'] = time() + $tokenData['refresh_token_expires_in'];
            }
            
            // Save token data - using the already defined secure token path
            // Make sure the directory exists
            if (!is_dir(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0755, true);
                logMessage('Created directory: ' . dirname($tokenPath));
            }
            
            if (file_put_contents($tokenPath, json_encode($tokenData))) {
                logMessage('Successfully saved token data to ' . $tokenPath);
                $loginResult = true;
                
                // For backward compatibility, copy to old locations
                $oldTokenLocations = [
                    __DIR__ . '/.ringcentral_token.json',
                    __DIR__ . '/temp/ringcentral_token.json'
                ];
                
                foreach ($oldTokenLocations as $oldPath) {
                    // Make sure directory exists
                    if (!is_dir(dirname($oldPath))) {
                        mkdir(dirname($oldPath), 0755, true);
                    }
                    
                    // Copy token for backward compatibility
                    if (copy($tokenPath, $oldPath)) {
                        logMessage("Copied token to legacy location: $oldPath for backwards compatibility");
                    }
                }
            } else {
                logMessage('Failed to save token data to ' . $tokenPath, 'ERROR');
                throw new Exception('Failed to save token data');
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error_description']) ? $errorData['error_description'] : 'Unknown error';
            logMessage('Token exchange failed: ' . $errorMessage, 'ERROR');
            throw new Exception('Failed to exchange code for token: ' . $errorMessage);
        }
    } catch (Exception $authException) {
        logMessage('Exception during login: ' . $authException->getMessage(), 'ERROR');
        throw $authException;
    }
    
    if ($loginResult) {
        logMessage('Successfully authenticated with RingCentral via OAuth');
        
        // Redirect to success page or show success message
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>RingCentral Authentication Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding: 40px; 
            text-align: center;
            background-color: #f8f9fa;
        }
        .success-box {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h2 class="text-success mb-4">RingCentral Authentication Successful!</h2>
        <p class="mb-4">Your Angel Stones Chat system has been successfully authenticated with RingCentral.</p>
        <p>You can now close this window and return to using the chat functionality.</p>
        <div class="mt-4">
            <a href="test_chat.html" class="btn btn-primary">Go to Chat Test Page</a>
        </div>
    </div>
</body>
</html>
HTML;
    } else {
        throw new Exception('Failed to authenticate with RingCentral');
    }
    
} catch (Exception $e) {
    // Log error
    logMessage("OAuth Error: " . $e->getMessage(), 'ERROR');
    
    // Show error page
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>RingCentral Authentication Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding: 40px; 
            text-align: center;
            background-color: #f8f9fa;
        }
        .error-box {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h2 class="text-danger mb-4">RingCentral Authentication Error</h2>
        <p class="mb-4">There was a problem authenticating with RingCentral:</p>
        <div class="alert alert-danger">
            {$e->getMessage()}
        </div>
        <div class="mt-4">
            <a href="authorize.php" class="btn btn-primary">Try Again</a>
        </div>
    </div>
</body>
</html>
HTML;
}
?>
