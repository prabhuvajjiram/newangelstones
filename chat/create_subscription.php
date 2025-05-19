<?php
/**
 * RingCentral Subscription Creation Tool
 * 
 * This script creates a subscription in RingCentral to receive real-time 
 * notifications about new messages.
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
    <title>Create RingCentral Subscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>RingCentral Subscription Creation</h1>
        
        <?php
        // Run automatically on page load or check if form was submitted
        $autoCreate = true; // Set to true to create automatically
        
        if ($autoCreate || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create')) {
            try {
                // Initialize the client
                $client = new RingCentralTeamMessagingClient();
                
                if (!$client->isAuthenticated()) {
                    throw new Exception("RingCentral client failed to authenticate. Please check your JWT token.");
                }
                
                // Get subscription parameters (set default values for auto-creation)
                $deliveryMode = $_POST['delivery_mode'] ?? 'WebHook';
                $notificationUrl = 'https://theangelstones.com/chat/api/webhook.php'; // Hardcoded URL
                $expiresIn = 7776000; // Maximum allowed: 90 days (7776000 seconds)
                
                if (empty($notificationUrl)) {
                    throw new Exception("Notification URL is required");
                }
                
                // Define the event filters
                $eventFilters = [];
                if (isset($_POST['filter_posts']) && $_POST['filter_posts'] === 'on') {
                    $eventFilters[] = "/restapi/v1.0/glip/posts";
                }
                if (isset($_POST['filter_groups']) && $_POST['filter_groups'] === 'on') {
                    $eventFilters[] = "/restapi/v1.0/glip/groups";
                }
                if (empty($eventFilters)) {
                    $eventFilters[] = "/restapi/v1.0/glip/posts"; // Default to posts
                }
                
                // Create the subscription
                $accessToken = $client->getAccessToken();
                $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/subscription';
                
                // Prepare the request
                $data = [
                    'eventFilters' => $eventFilters,
                    'deliveryMode' => [
                        'transportType' => $deliveryMode,
                        'address' => $notificationUrl
                    ],
                    'expiresIn' => $expiresIn
                ];
                
                // Send the request
                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                // Process the response
                if ($httpCode >= 200 && $httpCode < 300) {
                    $subscriptionData = json_decode($response, true);
                    echo '<div class="alert alert-success">';
                    echo '<h4>Subscription Created Successfully!</h4>';
                    echo '<p>Subscription ID: ' . ($subscriptionData['id'] ?? 'N/A') . '</p>';
                    echo '<p>Status: ' . ($subscriptionData['status'] ?? 'N/A') . '</p>';
                    echo '<p>Expiration: ' . ($subscriptionData['expirationTime'] ?? 'N/A') . '</p>';
                    echo '<p>Event Filters: ' . json_encode($subscriptionData['eventFilters'] ?? []) . '</p>';
                    echo '<p>Store this information for future reference.</p>';
                    echo '</div>';
                    
                    echo '<h4>Full Response</h4>';
                    echo '<pre class="bg-light p-3">' . json_encode($subscriptionData, JSON_PRETTY_PRINT) . '</pre>';
                } else {
                    $error = true;
                    $errorMessage = 'Failed to create subscription';
                }
                
                // Handle errors and display the response
                if ($error) {
                    // Provide detailed error information for debugging
                    echo json_encode([
                        'error' => true,
                        'message' => $errorMessage,
                        'details' => $response ?? 'No response data',
                        'http_code' => $httpCode ?? 'Unknown',
                        'curl_error' => $curlError ?? 'None'
                    ]);
                    exit;
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">';
                echo '<h4>Error</h4>';
                echo '<p>' . $e->getMessage() . '</p>';
                echo '</div>';
            }
        }
        
        // Display the form
        ?>
        
        <div class="card">
            <div class="card-header">
                Create New Subscription
            </div>
            <div class="card-body">
                <p class="text-info mb-3">
                    This form will create a subscription in RingCentral to receive real-time 
                    notifications when new messages are posted in your team chats.
                </p>
                
                <form method="post" action="">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="notification_url" class="form-label">Notification URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="notification_url" name="notification_url" 
                               placeholder="https://yourdomain.com/chat/api/webhook.php" required>
                        <div class="form-text">
                            This must be a public URL that RingCentral can reach. 
                            Cannot be localhost.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Event Filters</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="filter_posts" name="filter_posts" checked>
                            <label class="form-check-label" for="filter_posts">
                                Posts (/restapi/v1.0/glip/posts) - Receive notifications about new messages
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="filter_groups" name="filter_groups">
                            <label class="form-check-label" for="filter_groups">
                                Groups (/restapi/v1.0/glip/groups) - Receive notifications about group changes
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="delivery_mode" class="form-label">Delivery Mode</label>
                        <select class="form-select" id="delivery_mode" name="delivery_mode">
                            <option value="WebHook" selected>WebHook</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="expires_in" class="form-label">Expiration (seconds)</label>
                        <input type="number" class="form-control" id="expires_in" name="expires_in" 
                               value="7776000" min="60" max="7776000">
                        <div class="form-text">
                            How long the subscription should last before it needs to be renewed.
                            Using maximum allowed value: 7776000 seconds (90 days).
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Subscription</button>
                    <a href="test_chat.html" class="btn btn-secondary">Back to Test Chat</a>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                Important Notes
            </div>
            <div class="card-body">
                <h5>Requirements for Subscriptions</h5>
                <ul>
                    <li>Your webhook URL must be publicly accessible (not localhost)</li>
                    <li>The webhook endpoint must respond with HTTP 200 status to validation requests</li>
                    <li>The subscription expires automatically after the specified time</li>
                    <li>You'll need to renew or recreate the subscription before it expires</li>
                </ul>
                
                <h5>Deployment Checklist</h5>
                <ol>
                    <li>Deploy your chat system to a production server</li>
                    <li>Update the config.php file with production settings</li>
                    <li>Create a subscription using this tool with your production URL</li>
                    <li>Test the integration by sending messages and checking responses</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
