<?php
/**
 * Register RingCentral Webhook
 * 
 * This script registers a webhook subscription with RingCentral
 * to receive notifications when new messages are posted.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set JSON output
header('Content-Type: application/json');

// Create a log function
function logMessage($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "<br>\n";
    flush();
}

try {
    // Initialize the RingCentral client
    $clientConfig = [
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => RINGCENTRAL_JWT_TOKEN,
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json'
    ];
    
    logMessage("Initializing RingCentral client...");
    $rcClient = new RingCentralTeamMessagingClient($clientConfig);
    
    // Make sure we're authenticated
    if (!$rcClient->isAuthenticated()) {
        logMessage("Authenticating with RingCentral...");
        $authResult = $rcClient->authenticate();
        if (!$authResult) {
            throw new Exception("Failed to authenticate with RingCentral");
        }
        logMessage("Authentication successful!");
    } else {
        logMessage("Already authenticated with RingCentral.");
    }
    
    // Get the webhook URL
    $serverProtocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $serverHost = $_SERVER['HTTP_HOST'];
    $serverUri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $webhookUrl = $serverProtocol . $serverHost . $serverUri . '/api/webhook_handler.php';
    
    logMessage("Webhook URL: $webhookUrl");
    
    // Check for existing webhooks
    logMessage("Checking for existing webhooks...");
    $existingWebhooks = $rcClient->getSubscriptions();
    
    if (!empty($existingWebhooks) && is_array($existingWebhooks)) {
        foreach ($existingWebhooks as $webhook) {
            if (isset($webhook['deliveryMode']['address']) && 
                $webhook['deliveryMode']['address'] === $webhookUrl) {
                logMessage("Found existing webhook subscription (ID: {$webhook['id']})");
                
                // Delete the existing webhook
                logMessage("Deleting existing webhook...");
                $rcClient->deleteSubscription($webhook['id']);
                logMessage("Existing webhook deleted.");
                break;
            }
        }
    }
    
    // Create a new webhook subscription
    logMessage("Creating new webhook subscription...");
    
    // Define the webhook filters for team chat messages
    // Must follow exact format from RingCentral docs
    $eventFilters = [
        '/restapi/v1.0/glip/posts',
        '/restapi/v1.0/glip/groups'
    ];
    
    $webhookConfig = [
        'eventFilters' => $eventFilters,
        'deliveryMode' => [
            'transportType' => 'WebHook',
            'address' => $webhookUrl,
            'verificationToken' => substr(md5(uniqid(rand(), true)), 0, 20), // random verification token
            'expiresIn' => 604800 // 7 days in seconds
        ],
        'expirationTime' => date('Y-m-d\TH:i:s.000\Z', strtotime('+7 days'))
    ];
    
    $result = $rcClient->createSubscription($webhookConfig);
    
    if ($result && isset($result['id'])) {
        logMessage("Webhook registered successfully!");
        logMessage("Subscription ID: " . $result['id']);
        logMessage("Webhook will expire on: " . $result['expirationTime']);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Webhook registered successfully',
            'subscriptionId' => $result['id'],
            'expirationTime' => $result['expirationTime']
        ]);
    } else {
        throw new Exception("Failed to register webhook");
    }
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
