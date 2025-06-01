<?php
/**
 * Detailed JWT Debug Tool
 * 
 * This script provides comprehensive debugging information about
 * JWT authentication with RingCentral.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set output content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed JWT Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; white-space: pre-wrap; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <h1>Detailed JWT Authentication Debug</h1>
        
        <div class="card">
            <div class="card-header">JWT Token Information</div>
            <div class="card-body">
                <?php
                if (!defined('RINGCENTRAL_JWT_TOKEN') || empty(RINGCENTRAL_JWT_TOKEN)) {
                    echo '<div class="alert alert-danger">JWT token is not defined or empty</div>';
                } else {
                    $tokenParts = explode('.', RINGCENTRAL_JWT_TOKEN);
                    if (count($tokenParts) !== 3) {
                        echo '<div class="alert alert-danger">Invalid JWT token format. Expected 3 parts (header.payload.signature)</div>';
                    } else {
                        echo '<p class="text-success">✓ JWT token has valid format (header.payload.signature)</p>';
                        
                        // Decode header
                        $headerBase64 = $tokenParts[0];
                        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $headerBase64)), true);
                        
                        echo '<h5>JWT Header</h5>';
                        echo '<pre>' . json_encode($header, JSON_PRETTY_PRINT) . '</pre>';
                        
                        // Decode payload
                        $payloadBase64 = $tokenParts[1];
                        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadBase64)), true);
                        
                        echo '<h5>JWT Payload</h5>';
                        echo '<pre>' . json_encode($payload, JSON_PRETTY_PRINT) . '</pre>';
                        
                        // Check expiration
                        if (isset($payload['exp'])) {
                            $expTime = (int)$payload['exp'];
                            $currentTime = time();
                            $diff = $expTime - $currentTime;
                            
                            echo '<h5>Expiration Check</h5>';
                            echo '<ul>';
                            echo '<li>Expiration timestamp: ' . $expTime . ' (' . date('Y-m-d H:i:s', $expTime) . ')</li>';
                            echo '<li>Current timestamp: ' . $currentTime . ' (' . date('Y-m-d H:i:s', $currentTime) . ')</li>';
                            
                            if ($diff > 0) {
                                echo '<li class="text-success">✓ Token is valid for another ' . secondsToHuman($diff) . '</li>';
                            } else {
                                echo '<li class="text-danger">✗ Token expired ' . secondsToHuman(abs($diff)) . ' ago!</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-warning">JWT token does not contain an expiration (exp) claim</p>';
                        }
                        
                        // Check subject
                        if (isset($payload['sub'])) {
                            echo '<p>Subject (user ID): ' . $payload['sub'] . '</p>';
                        }
                        
                        // Check audience
                        if (isset($payload['aud'])) {
                            echo '<p>Audience: ' . $payload['aud'] . '</p>';
                        }
                    }
                }
                
                function secondsToHuman($seconds) {
                    $days = floor($seconds / 86400);
                    $seconds %= 86400;
                    $hours = floor($seconds / 3600);
                    $seconds %= 3600;
                    $minutes = floor($seconds / 60);
                    $seconds %= 60;
                    
                    $result = '';
                    if ($days > 0) $result .= "$days days, ";
                    if ($hours > 0) $result .= "$hours hours, ";
                    if ($minutes > 0) $result .= "$minutes minutes, ";
                    $result .= "$seconds seconds";
                    
                    return $result;
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">RingCentral API Credentials</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Client ID:</strong> 
                        <?php echo defined('RINGCENTRAL_CLIENT_ID') && !empty(RINGCENTRAL_CLIENT_ID) ? 
                            '<span class="text-success">' . RINGCENTRAL_CLIENT_ID . '</span>' : 
                            '<span class="text-danger">Not defined or empty</span>'; ?>
                    </li>
                    <li class="list-group-item">
                        <strong>Client Secret:</strong> 
                        <?php echo defined('RINGCENTRAL_CLIENT_SECRET') && !empty(RINGCENTRAL_CLIENT_SECRET) ? 
                            '<span class="text-success">' . substr(RINGCENTRAL_CLIENT_SECRET, 0, 3) . '...' . substr(RINGCENTRAL_CLIENT_SECRET, -3) . '</span>' : 
                            '<span class="text-danger">Not defined or empty</span>'; ?>
                    </li>
                    <li class="list-group-item">
                        <strong>Server URL:</strong> 
                        <?php echo defined('RINGCENTRAL_SERVER') && !empty(RINGCENTRAL_SERVER) ? 
                            '<span class="text-success">' . RINGCENTRAL_SERVER . '</span>' : 
                            '<span class="text-danger">Not defined or empty</span>'; ?>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Direct Authentication Test</div>
            <div class="card-body">
                <h5>Testing JWT Authentication</h5>
                <?php
                // Prepare the auth request
                if (defined('RINGCENTRAL_JWT_TOKEN') && !empty(RINGCENTRAL_JWT_TOKEN) && 
                    defined('RINGCENTRAL_CLIENT_ID') && !empty(RINGCENTRAL_CLIENT_ID) && 
                    defined('RINGCENTRAL_CLIENT_SECRET') && !empty(RINGCENTRAL_CLIENT_SECRET)) {
                    
                    $endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
                    $data = [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => RINGCENTRAL_JWT_TOKEN
                    ];
                    
                    echo '<p>Testing authentication with endpoint: <code>' . $endpoint . '</code></p>';
                    
                    // Prepare the request
                    $ch = curl_init($endpoint);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    
                    // Debugging options
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    
                    // Get verbose info
                    curl_setopt($ch, CURLOPT_VERBOSE, true);
                    $verbose = fopen('php://temp', 'w+');
                    curl_setopt($ch, CURLOPT_STDERR, $verbose);
                    
                    // Execute the request
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    $info = curl_getinfo($ch);
                    curl_close($ch);
                    
                    // Get verbose log
                    rewind($verbose);
                    $verboseLog = stream_get_contents($verbose);
                    fclose($verbose);
                    
                    // Display results
                    echo '<div class="row">';
                    echo '<div class="col-md-6">';
                    echo '<h6>Request Data</h6>';
                    echo '<pre>' . http_build_query($data) . '</pre>';
                    echo '</div>';
                    
                    echo '<div class="col-md-6">';
                    echo '<h6>HTTP Response Code</h6>';
                    if ($httpCode == 200) {
                        echo '<p class="text-success">✓ ' . $httpCode . ' (Success)</p>';
                    } else {
                        echo '<p class="text-danger">✗ ' . $httpCode . ' (Error)</p>';
                    }
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<h6>Response Body</h6>';
                    if (!empty($response)) {
                        // Try to format as JSON
                        $jsonResponse = json_decode($response, true);
                        if ($jsonResponse) {
                            echo '<pre>' . json_encode($jsonResponse, JSON_PRETTY_PRINT) . '</pre>';
                            
                            // Check for error information
                            if (isset($jsonResponse['error'])) {
                                echo '<div class="alert alert-danger">';
                                echo '<strong>Error:</strong> ' . $jsonResponse['error'] . '<br>';
                                if (isset($jsonResponse['error_description'])) {
                                    echo '<strong>Description:</strong> ' . $jsonResponse['error_description'];
                                }
                                echo '</div>';
                                
                                // Provide solutions based on error
                                if ($jsonResponse['error'] == 'invalid_client') {
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>Possible Solutions:</strong>';
                                    echo '<ul>';
                                    echo '<li>Double-check that your Client ID and Client Secret are correct</li>';
                                    echo '<li>Ensure your app is properly configured in the RingCentral Developer Console</li>';
                                    echo '<li>Verify that your app has the required permissions (scopes)</li>';
                                    echo '</ul>';
                                    echo '</div>';
                                } elseif ($jsonResponse['error'] == 'invalid_grant') {
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>Possible Solutions:</strong>';
                                    echo '<ul>';
                                    echo '<li>Your JWT token may be invalid or malformed</li>';
                                    echo '<li>The token might be expired (check expiration above)</li>';
                                    echo '<li>The token might not have the required scopes</li>';
                                    echo '<li>Generate a new JWT token in the RingCentral Developer Console</li>';
                                    echo '</ul>';
                                    echo '</div>';
                                }
                            }
                            
                            // If we got a successful response
                            if (isset($jsonResponse['access_token'])) {
                                echo '<div class="alert alert-success">';
                                echo '<strong>✓ Authentication Successful!</strong><br>';
                                echo 'Received access token: ' . substr($jsonResponse['access_token'], 0, 10) . '...<br>';
                                echo 'Token expires in: ' . ($jsonResponse['expires_in'] ?? 'unknown') . ' seconds';
                                echo '</div>';
                            }
                        } else {
                            echo '<pre>' . htmlspecialchars($response) . '</pre>';
                        }
                    } else {
                        echo '<p class="text-danger">Empty response</p>';
                        if (!empty($curlError)) {
                            echo '<div class="alert alert-danger">CURL Error: ' . $curlError . '</div>';
                        }
                    }
                    
                    echo '<h6>CURL Info</h6>';
                    echo '<pre>' . print_r($info, true) . '</pre>';
                    
                    echo '<h6>Verbose Log</h6>';
                    echo '<pre>' . htmlspecialchars($verboseLog) . '</pre>';
                    
                } else {
                    echo '<div class="alert alert-danger">Missing required configuration (JWT token, Client ID, or Client Secret)</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">API Test - Get Team Chats</div>
            <div class="card-body">
                <?php
                // Test getting team chats if we have authentication
                if (isset($jsonResponse) && isset($jsonResponse['access_token'])) {
                    $accessToken = $jsonResponse['access_token'];
                    
                    // Try to get team chats
                    $chatsEndpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/groups';
                    $ch = curl_init($chatsEndpoint);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $accessToken
                    ]);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    
                    $chatsResponse = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    echo '<h5>Team Chats API Response</h5>';
                    echo '<p>HTTP Code: ' . $httpCode . '</p>';
                    
                    if ($httpCode == 200) {
                        $chatsData = json_decode($chatsResponse, true);
                        if (isset($chatsData['records'])) {
                            echo '<p class="text-success">✓ Successfully retrieved ' . count($chatsData['records']) . ' team chats</p>';
                            
                            if (count($chatsData['records']) > 0) {
                                echo '<h6>First 5 Team Chats</h6>';
                                echo '<ul class="list-group">';
                                for ($i = 0; $i < min(5, count($chatsData['records'])); $i++) {
                                    $chat = $chatsData['records'][$i];
                                    echo '<li class="list-group-item">';
                                    echo '<strong>ID:</strong> ' . ($chat['id'] ?? 'N/A') . '<br>';
                                    echo '<strong>Name:</strong> ' . ($chat['name'] ?? 'N/A') . '<br>';
                                    echo '<strong>Type:</strong> ' . ($chat['type'] ?? 'N/A') . '<br>';
                                    echo '<strong>Members:</strong> ' . (isset($chat['members']) ? count($chat['members']) : 0);
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        } else {
                            echo '<p class="text-warning">Unexpected response format</p>';
                            echo '<pre>' . json_encode(json_decode($chatsResponse, true), JSON_PRETTY_PRINT) . '</pre>';
                        }
                    } else {
                        echo '<p class="text-danger">Failed to retrieve team chats</p>';
                        echo '<pre>' . json_encode(json_decode($chatsResponse, true), JSON_PRETTY_PRINT) . '</pre>';
                    }
                } else {
                    echo '<div class="alert alert-warning">Cannot test API access because authentication failed</div>';
                }
                ?>
            </div>
        </div>
        
        <h2>Recommendations</h2>
        <div class="card">
            <div class="card-body">
                <h5>Production Implementation</h5>
                <p>For your production environment:</p>
                <ol>
                    <li>Deploy your code to a server with a public URL</li>
                    <li>Create a webhook in RingCentral pointing to your server: <code>https://yourdomain.com/chat/api/webhook.php</code></li>
                    <li>Update your JWT token if needed using the information from this debug page</li>
                    <li>Add SSL certificate to your server for secure communication</li>
                </ol>
                
                <h5>Local Testing</h5>
                <p>For local development testing:</p>
                <ol>
                    <li>Use the message simulator for testing message display</li>
                    <li>Or, use a tunneling service like ngrok to expose your localhost to the internet</li>
                    <li>Fix any JWT authentication issues identified on this page</li>
                </ol>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="test_chat.html" class="btn btn-primary">Back to Test Chat</a>
            <a href="simple_message_simulator.php" class="btn btn-secondary">Message Simulator</a>
        </div>
    </div>
</body>
</html>
