<?php
/**
 * RingCentral Webhook Debug Tool
 * 
 * This script examines webhook logs and sends a test notification to 
 * help diagnose why webhook messages aren't being received.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RingCentral Webhook Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre.code {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .webhook-log {
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>RingCentral Webhook Debug</h1>
        
        <?php
        $webhookLogFile = __DIR__ . '/webhook.log';
        $errorLogFile = __DIR__ . '/error.log';
        
        // Check webhook registration
        try {
            // Initialize RingCentral client
            $client = new RingCentralTeamMessagingClient();
            
            if (!$client->isAuthenticated()) {
                echo '<div class="alert alert-danger">
                    <h4>Authentication Failed</h4>
                    <p>Could not authenticate with RingCentral. Check your JWT token.</p>
                </div>';
            } else {
                echo '<div class="alert alert-success">
                    <h4>Authentication Successful</h4>
                    <p>Successfully authenticated with RingCentral.</p>
                </div>';
                
                // Get access token
                $accessToken = $client->getAccessToken();
                
                // Check for existing subscriptions
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
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($httpCode == 200) {
                    $subscriptionData = json_decode($response, true);
                    
                    echo '<div class="card mb-4">
                        <div class="card-header">Active Subscriptions</div>
                        <div class="card-body">';
                    
                    if (isset($subscriptionData['records']) && count($subscriptionData['records']) > 0) {
                        foreach ($subscriptionData['records'] as $index => $subscription) {
                            echo '<div class="card mb-3">
                                <div class="card-header">Subscription #' . ($index + 1) . '</div>
                                <div class="card-body">
                                    <p><strong>ID:</strong> ' . $subscription['id'] . '</p>
                                    <p><strong>Status:</strong> ' . $subscription['status'] . '</p>
                                    <p><strong>Created:</strong> ' . $subscription['creationTime'] . '</p>
                                    <p><strong>Expires:</strong> ' . $subscription['expirationTime'] . '</p>
                                    <p><strong>Delivery Mode:</strong> ' . $subscription['deliveryMode']['transportType'] . '</p>
                                    <p><strong>Webhook URL:</strong> ' . $subscription['deliveryMode']['address'] . '</p>
                                    <p><strong>Event Filters:</strong></p>
                                    <ul>';
                            
                            foreach ($subscription['eventFilters'] as $filter) {
                                echo '<li>' . $filter . '</li>';
                            }
                            
                            echo '</ul>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning">No active subscriptions found.</div>';
                    }
                    
                    echo '</div></div>';
                } else {
                    echo '<div class="alert alert-danger">
                        <h4>Failed to retrieve subscriptions</h4>
                        <p>HTTP Code: ' . $httpCode . '</p>
                        <p>Error: ' . $curlError . '</p>
                        <p>Response: ' . $response . '</p>
                    </div>';
                }
                
                // Test sending a message to the chat
                if (isset($_POST['action']) && $_POST['action'] === 'test_message') {
                    $chatId = RINGCENTRAL_TEAM_CHAT_ID;
                    $message = 'Test notification from webhook debug tool at ' . date('Y-m-d H:i:s');
                    
                    $sendResult = $client->sendTeamChatMessage($chatId, $message);
                    
                    if ($sendResult) {
                        echo '<div class="alert alert-success">
                            <h4>Test Message Sent</h4>
                            <p>Successfully sent a test message to the RingCentral team chat.</p>
                        </div>';
                    } else {
                        echo '<div class="alert alert-danger">
                            <h4>Failed to Send Test Message</h4>
                            <p>Error sending message to the team chat.</p>
                        </div>';
                    }
                }
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">
                <h4>Error</h4>
                <p>' . $e->getMessage() . '</p>
            </div>';
        }
        
        // Check webhook log
        echo '<div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Webhook Log</span>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="test_message">
                    <button type="submit" class="btn btn-primary btn-sm">Send Test Message</button>
                </form>
            </div>
            <div class="card-body">';
        
        if (file_exists($webhookLogFile)) {
            $logContent = file_get_contents($webhookLogFile);
            $lines = array_reverse(explode("\n", $logContent));
            $lines = array_slice($lines, 0, 100); // Show last 100 lines
            
            echo '<div class="webhook-log">';
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                // Highlight error lines
                if (stripos($line, 'error') !== false) {
                    echo '<div class="text-danger">' . htmlspecialchars($line) . '</div>';
                } else {
                    echo htmlspecialchars($line) . '<br>';
                }
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">No webhook log file found.</div>';
        }
        
        echo '</div></div>';
        
        // Check error log
        echo '<div class="card mb-4">
            <div class="card-header">PHP Error Log</div>
            <div class="card-body">';
        
        if (file_exists($errorLogFile)) {
            $logContent = file_get_contents($errorLogFile);
            $lines = array_reverse(explode("\n", $logContent));
            $lines = array_slice($lines, 0, 50); // Show last 50 lines
            
            echo '<div class="webhook-log">';
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                echo htmlspecialchars($line) . '<br>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">No PHP error log file found.</div>';
        }
        
        echo '</div></div>';
        
        // Check webhook endpoint accessibility
        $webhookUrl = 'https://theangelstones.com/chat/api/webhook.php';
        
        echo '<div class="card mb-4">
            <div class="card-header">Webhook Endpoint Check</div>
            <div class="card-body">';
        
        // Try a direct GET request to the webhook endpoint
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo '<h5>GET Request to ' . $webhookUrl . '</h5>';
        echo '<p><strong>Status Code:</strong> ' . $httpCode . '</p>';
        
        if (!empty($error)) {
            echo '<p><strong>Error:</strong> ' . $error . '</p>';
        }
        
        echo '<p><strong>Response Headers:</strong></p>';
        echo '<pre class="code">' . htmlspecialchars($header) . '</pre>';
        
        echo '<p><strong>Response Body:</strong></p>';
        echo '<pre class="code">' . htmlspecialchars($body) . '</pre>';
        
        // Now try with a validation token (simulating RingCentral validation)
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'validation-token: test-validation-token-' . time()
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo '<h5>GET Request with Validation Token</h5>';
        echo '<p><strong>Status Code:</strong> ' . $httpCode . '</p>';
        
        if (!empty($error)) {
            echo '<p><strong>Error:</strong> ' . $error . '</p>';
        }
        
        echo '<p><strong>Response Headers:</strong></p>';
        echo '<pre class="code">' . htmlspecialchars($header) . '</pre>';
        
        echo '<p><strong>Response Body:</strong></p>';
        echo '<pre class="code">' . htmlspecialchars($body) . '</pre>';
        
        // Check if validation token is echoed back
        $validationEchoed = (strpos($header, 'validation-token') !== false);
        
        if ($validationEchoed) {
            echo '<div class="alert alert-success">
                <p>Your webhook endpoint correctly echoes back the validation token!</p>
            </div>';
        } else {
            echo '<div class="alert alert-warning">
                <p>Your webhook endpoint does not echo back the validation token. This is required for RingCentral to validate your webhook.</p>
            </div>';
        }
        
        echo '</div></div>';
        ?>
        
        <div class="card mb-4">
            <div class="card-header">Potential Issues and Solutions</div>
            <div class="card-body">
                <h5>Common Webhook Issues:</h5>
                <ol>
                    <li><strong>Subscription Not Active</strong> - Check if you have an active subscription above</li>
                    <li><strong>Webhook Validation Failure</strong> - The webhook endpoint must echo back the validation token</li>
                    <li><strong>Firewall Blocking</strong> - Ensure your server firewall allows incoming connections from RingCentral</li>
                    <li><strong>Apache/PHP Configuration</strong> - Ensure HTTP headers are properly passed to PHP</li>
                    <li><strong>cURL Extension</strong> - Ensure PHP cURL extension is enabled</li>
                </ol>
                
                <h5>Recommendations:</h5>
                <ol>
                    <li>Review the webhook log for any errors</li>
                    <li>Try creating a new subscription using the <a href="direct_subscription_create.php">direct subscription script</a></li>
                    <li>Check if the webhook URL matches exactly what's registered with RingCentral</li>
                    <li>Make sure your server's firewall allows incoming traffic from RingCentral</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
