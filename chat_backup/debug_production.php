<?php
/**
 * Production Debugging Tool
 * 
 * This script provides detailed diagnostics for RingCentral integration issues in production.
 * It checks all aspects of the integration and provides specific recommendations.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RingCentral Integration Debug</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        h2 { color: #3498db; margin-top: 30px; }
        h3 { color: #2980b9; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; overflow: hidden; }
        .card-header { background: #f5f5f5; padding: 10px 15px; font-weight: bold; border-bottom: 1px solid #ddd; }
        .card-body { padding: 15px; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .info { color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background-color: #f5f5f5; }
        .debug-box { background: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .btn { display: inline-block; padding: 8px 16px; margin: 5px 0; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-primary { background: #3498db; }
        .btn-secondary { background: #95a5a6; }
        .btn-success { background: #2ecc71; }
        .btn-danger { background: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>RingCentral Integration Diagnostic Tool</h1>
        <p>This tool performs comprehensive checks on your RingCentral integration.</p>
        
        <div class="card">
            <div class="card-header">Configuration Check</div>
            <div class="card-body">
                <?php
                // Check configuration settings
                $configRequirements = [
                    'RINGCENTRAL_CLIENT_ID' => defined('RINGCENTRAL_CLIENT_ID') && !empty(RINGCENTRAL_CLIENT_ID),
                    'RINGCENTRAL_CLIENT_SECRET' => defined('RINGCENTRAL_CLIENT_SECRET') && !empty(RINGCENTRAL_CLIENT_SECRET),
                    'RINGCENTRAL_JWT_TOKEN' => defined('RINGCENTRAL_JWT_TOKEN') && !empty(RINGCENTRAL_JWT_TOKEN),
                    'RINGCENTRAL_SERVER' => defined('RINGCENTRAL_SERVER') && !empty(RINGCENTRAL_SERVER),
                    'RINGCENTRAL_TEAM_CHAT_ID' => defined('RINGCENTRAL_TEAM_CHAT_ID') && !empty(RINGCENTRAL_TEAM_CHAT_ID)
                ];
                
                $configErrors = 0;
                echo '<table>';
                echo '<tr><th>Configuration Item</th><th>Status</th><th>Value</th></tr>';
                
                foreach ($configRequirements as $key => $isValid) {
                    echo '<tr>';
                    echo '<td>' . $key . '</td>';
                    
                    if ($isValid) {
                        echo '<td class="success">✓ Valid</td>';
                        
                        // Show value (mask sensitive data)
                        if ($key == 'RINGCENTRAL_CLIENT_SECRET') {
                            echo '<td>' . substr(constant($key), 0, 3) . '...' . substr(constant($key), -3) . '</td>';
                        } elseif ($key == 'RINGCENTRAL_JWT_TOKEN') {
                            echo '<td>' . substr(constant($key), 0, 10) . '...' . substr(constant($key), -5) . '</td>';
                        } else {
                            echo '<td>' . constant($key) . '</td>';
                        }
                    } else {
                        $configErrors++;
                        echo '<td class="error">✗ Missing or empty</td>';
                        echo '<td>N/A</td>';
                    }
                    
                    echo '</tr>';
                }
                
                echo '</table>';
                
                if ($configErrors > 0) {
                    echo '<div class="error">Found ' . $configErrors . ' configuration issues that need to be resolved.</div>';
                } else {
                    echo '<div class="success">All required configuration variables are present.</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">JWT Token Validation</div>
            <div class="card-body">
                <?php
                // Parse and validate JWT token
                if (defined('RINGCENTRAL_JWT_TOKEN') && !empty(RINGCENTRAL_JWT_TOKEN)) {
                    $tokenParts = explode('.', RINGCENTRAL_JWT_TOKEN);
                    
                    if (count($tokenParts) !== 3) {
                        echo '<div class="error">Invalid JWT token format. Expected 3 parts (header.payload.signature).</div>';
                    } else {
                        echo '<div class="success">JWT token has valid format (header.payload.signature).</div>';
                        
                        // Decode header
                        $headerBase64 = $tokenParts[0];
                        $headerJSON = base64_decode(str_replace(['-', '_'], ['+', '/'], $headerBase64 . '=='));
                        $header = json_decode($headerJSON, true);
                        
                        // Decode payload
                        $payloadBase64 = $tokenParts[1];
                        $payloadJSON = base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadBase64 . '=='));
                        $payload = json_decode($payloadJSON, true);
                        
                        echo '<h3>JWT Header</h3>';
                        echo '<pre>' . json_encode($header, JSON_PRETTY_PRINT) . '</pre>';
                        
                        echo '<h3>JWT Payload</h3>';
                        echo '<pre>' . json_encode($payload, JSON_PRETTY_PRINT) . '</pre>';
                        
                        // Check expiration
                        if (isset($payload['exp'])) {
                            $expTime = (int)$payload['exp'];
                            $currentTime = time();
                            $diff = $expTime - $currentTime;
                            
                            echo '<h3>Expiration Check</h3>';
                            echo '<ul>';
                            echo '<li>Expiration timestamp: ' . $expTime . ' (' . date('Y-m-d H:i:s', $expTime) . ')</li>';
                            echo '<li>Current timestamp: ' . $currentTime . ' (' . date('Y-m-d H:i:s', $currentTime) . ')</li>';
                            
                            if ($diff > 0) {
                                echo '<li class="success">✓ Token is valid for another ' . formatTimeInterval($diff) . '</li>';
                            } else {
                                echo '<li class="error">✗ Token expired ' . formatTimeInterval(abs($diff)) . ' ago!</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<div class="warning">JWT token does not contain an expiration (exp) claim. This is unusual.</div>';
                        }
                    }
                } else {
                    echo '<div class="error">JWT token is not defined or is empty.</div>';
                }
                
                function formatTimeInterval($seconds) {
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
            <div class="card-header">Authentication Test</div>
            <div class="card-body">
                <?php
                try {
                    // Attempt authentication with detailed debugging
                    $authResult = directJwtAuthTest();
                    
                    if ($authResult['success']) {
                        echo '<div class="success">✓ Authentication successful!</div>';
                        echo '<p>Access token obtained: ' . substr($authResult['access_token'], 0, 10) . '...</p>';
                        echo '<p>Token expires in: ' . $authResult['expires_in'] . ' seconds</p>';
                    } else {
                        echo '<div class="error">✗ Authentication failed!</div>';
                        if (!empty($authResult['error'])) {
                            echo '<p class="error">Error: ' . $authResult['error'] . '</p>';
                            echo '<p>Error description: ' . ($authResult['error_description'] ?? 'None provided') . '</p>';
                        }
                    }
                    
                    echo '<h3>Full Response</h3>';
                    echo '<pre>' . json_encode($authResult, JSON_PRETTY_PRINT) . '</pre>';
                    
                } catch (Exception $e) {
                    echo '<div class="error">Error during authentication test: ' . $e->getMessage() . '</div>';
                }
                
                function directJwtAuthTest() {
                    // This performs a direct JWT authentication test without using the client class
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
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    
                    // Get verbose output
                    curl_setopt($ch, CURLOPT_VERBOSE, true);
                    $verbose = fopen('php://temp', 'w+');
                    curl_setopt($ch, CURLOPT_STDERR, $verbose);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    $info = curl_getinfo($ch);
                    curl_close($ch);
                    
                    // Get verbose log
                    rewind($verbose);
                    $verboseLog = stream_get_contents($verbose);
                    fclose($verbose);
                    
                    $result = [
                        'success' => false,
                        'http_code' => $httpCode,
                        'curl_error' => $curlError,
                        'info' => $info,
                        'verbose_log' => $verboseLog
                    ];
                    
                    if ($httpCode >= 200 && $httpCode < 300) {
                        $responseData = json_decode($response, true);
                        if (isset($responseData['access_token'])) {
                            $result['success'] = true;
                            $result = array_merge($result, $responseData);
                        } else {
                            $result['error'] = 'No access_token in response';
                            $result['response'] = $response;
                        }
                    } else {
                        $errorData = json_decode($response, true);
                        if ($errorData) {
                            $result = array_merge($result, $errorData);
                        } else {
                            $result['error'] = 'HTTP error';
                            $result['response'] = $response;
                        }
                    }
                    
                    return $result;
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Database Check</div>
            <div class="card-body">
                <?php
                try {
                    // Check database connection and structure
                    require_once __DIR__ . '/db.php';
                    
                    // Get database connection
                    $db = getDb();
                    echo '<div class="success">✓ Database connection successful</div>';
                    
                    // Check tables
                    $requiredTables = ['chat_sessions', 'chat_messages', 'chat_settings'];
                    $existingTables = [];
                    
                    $stmt = $db->query("SHOW TABLES");
                    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                        $existingTables[] = $row[0];
                    }
                    
                    echo '<h3>Table Check</h3>';
                    echo '<table>';
                    echo '<tr><th>Table Name</th><th>Status</th></tr>';
                    
                    foreach ($requiredTables as $table) {
                        echo '<tr>';
                        echo '<td>' . $table . '</td>';
                        
                        if (in_array($table, $existingTables)) {
                            echo '<td class="success">✓ Exists</td>';
                        } else {
                            echo '<td class="error">✗ Missing</td>';
                        }
                        
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // Check chat_messages table structure
                    if (in_array('chat_messages', $existingTables)) {
                        echo '<h3>chat_messages Column Check</h3>';
                        $columns = [];
                        
                        $stmt = $db->query("SHOW COLUMNS FROM chat_messages");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $columns[] = $row['Field'];
                        }
                        
                        $requiredColumns = ['message_id', 'ring_central_message_id', 'sender_type'];
                        
                        echo '<table>';
                        echo '<tr><th>Column Name</th><th>Status</th></tr>';
                        
                        foreach ($requiredColumns as $column) {
                            echo '<tr>';
                            echo '<td>' . $column . '</td>';
                            
                            if (in_array($column, $columns)) {
                                echo '<td class="success">✓ Exists</td>';
                            } else {
                                echo '<td class="error">✗ Missing</td>';
                            }
                            
                            echo '</tr>';
                        }
                        
                        echo '</table>';
                    }
                    
                    // Check for existing messages
                    if (in_array('chat_messages', $existingTables)) {
                        $stmt = $db->query("SELECT COUNT(*) as count FROM chat_messages");
                        $messageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        echo '<h3>Message Statistics</h3>';
                        echo '<p>Total messages in database: ' . $messageCount . '</p>';
                        
                        if ($messageCount > 0) {
                            $stmt = $db->query("SELECT sender_type, COUNT(*) as count FROM chat_messages GROUP BY sender_type");
                            $typeCounts = [];
                            
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $typeCounts[$row['sender_type']] = $row['count'];
                            }
                            
                            echo '<table>';
                            echo '<tr><th>Sender Type</th><th>Count</th></tr>';
                            
                            foreach ($typeCounts as $type => $count) {
                                echo '<tr>';
                                echo '<td>' . $type . '</td>';
                                echo '<td>' . $count . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                            
                            // Check for agent messages
                            $agentCount = $typeCounts['agent'] ?? 0;
                            if ($agentCount == 0) {
                                echo '<div class="warning">No agent messages found. This may indicate that messages from RingCentral are not being received.</div>';
                            } else {
                                echo '<div class="success">Found ' . $agentCount . ' agent messages. Messages from RingCentral are being received.</div>';
                            }
                            
                            // Sample of recent messages
                            $stmt = $db->query("SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 5");
                            $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo '<h3>Recent Messages</h3>';
                            echo '<table>';
                            echo '<tr><th>ID</th><th>Type</th><th>Message</th><th>Created At</th></tr>';
                            
                            foreach ($recentMessages as $msg) {
                                echo '<tr>';
                                echo '<td>' . $msg['id'] . '</td>';
                                echo '<td>' . $msg['sender_type'] . '</td>';
                                echo '<td>' . htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '') . '</td>';
                                echo '<td>' . $msg['created_at'] . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                        }
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error">Database error: ' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Subscription Status</div>
            <div class="card-body">
                <?php
                try {
                    // Check for existing subscriptions
                    $subscriptions = checkSubscriptions();
                    
                    if ($subscriptions['success']) {
                        $records = $subscriptions['records'] ?? [];
                        echo '<div class="success">Successfully retrieved subscription information</div>';
                        echo '<p>Found ' . count($records) . ' active subscriptions</p>';
                        
                        if (count($records) > 0) {
                            echo '<table>';
                            echo '<tr><th>ID</th><th>Status</th><th>Event Filters</th><th>Expiration</th></tr>';
                            
                            foreach ($records as $sub) {
                                echo '<tr>';
                                echo '<td>' . ($sub['id'] ?? 'N/A') . '</td>';
                                echo '<td>' . ($sub['status'] ?? 'N/A') . '</td>';
                                echo '<td>' . implode('<br>', $sub['eventFilters'] ?? []) . '</td>';
                                echo '<td>' . ($sub['expirationTime'] ?? 'N/A') . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                            
                            // Find notification URL
                            $ourWebhook = false;
                            $targetUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/chat/api/webhook.php';
                            
                            foreach ($records as $sub) {
                                $deliveryMode = $sub['deliveryMode'] ?? [];
                                $address = $deliveryMode['address'] ?? '';
                                
                                if (strpos($address, $_SERVER['HTTP_HOST']) !== false) {
                                    $ourWebhook = true;
                                    break;
                                }
                            }
                            
                            if ($ourWebhook) {
                                echo '<div class="success">✓ Subscription for this domain exists</div>';
                            } else {
                                echo '<div class="warning">No subscription was found pointing to ' . $_SERVER['HTTP_HOST'] . '</div>';
                                echo '<p>You may need to create a subscription with the webhook URL: ' . $targetUrl . '</p>';
                            }
                        } else {
                            echo '<div class="warning">No active subscriptions found</div>';
                            echo '<p>You need to create a subscription for webhook notifications</p>';
                        }
                    } else {
                        echo '<div class="error">Failed to retrieve subscription information</div>';
                        if (isset($subscriptions['error'])) {
                            echo '<p>Error: ' . $subscriptions['error'] . '</p>';
                        }
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error">Error checking subscriptions: ' . $e->getMessage() . '</div>';
                }
                
                function checkSubscriptions() {
                    $result = directJwtAuthTest();
                    
                    if (!$result['success']) {
                        return [
                            'success' => false,
                            'error' => 'Authentication failed'
                        ];
                    }
                    
                    $accessToken = $result['access_token'];
                    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/subscription';
                    
                    $ch = curl_init($endpoint);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $accessToken
                    ]);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode >= 200 && $httpCode < 300) {
                        $data = json_decode($response, true);
                        return array_merge(['success' => true], $data);
                    } else {
                        $errorData = json_decode($response, true);
                        return [
                            'success' => false,
                            'http_code' => $httpCode,
                            'error' => isset($errorData['error']) ? $errorData['error'] : 'HTTP error ' . $httpCode,
                            'response' => $response
                        ];
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Diagnostic Tools</div>
            <div class="card-body">
                <h3>Available Actions</h3>
                <div class="btn-group">
                    <a href="create_subscription.php" class="btn btn-primary">Create Subscription</a>
                    <a href="renew_subscription.php" class="btn btn-success">Renew Subscription</a>
                    <a href="simple_message_simulator.php" class="btn btn-secondary">Message Simulator</a>
                </div>
                
                <h3>Common Issues & Solutions</h3>
                <div class="debug-box">
                    <h4>Authentication Errors</h4>
                    <ul>
                        <li><strong>invalid_client:</strong> Check your Client ID and Client Secret</li>
                        <li><strong>invalid_grant:</strong> JWT token is expired or malformed</li>
                        <li><strong>Connection refused:</strong> Network issue or API endpoint is incorrect</li>
                    </ul>
                    
                    <h4>Message Receiving Issues</h4>
                    <ul>
                        <li><strong>No agent messages in database:</strong> Webhook/subscription is not properly set up</li>
                        <li><strong>Messages in database but not displayed:</strong> Polling mechanism issue</li>
                        <li><strong>Missing message_id column:</strong> Database schema needs to be updated</li>
                    </ul>
                    
                    <h4>Next Steps</h4>
                    <ol>
                        <li>Fix any configuration issues identified above</li>
                        <li>Create or renew subscription if needed</li>
                        <li>Ensure webhook.php endpoint is accessible from the internet</li>
                        <li>Test sending and receiving messages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
