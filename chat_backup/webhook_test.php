<?php
/**
 * Simple Webhook Test Script
 * Tests if webhook validation and processing are working correctly
 */

// Include necessary files
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Initialize
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Create log function
function logTest($message, $level = 'INFO') {
    $logFile = __DIR__ . '/webhook_test.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        $logFile, 
        "[$timestamp] [$level] $message\n", 
        FILE_APPEND
    );
}

// Check if we received a validation token header
$headers = getallheaders();
logTest("Headers received: " . json_encode($headers));

if (isset($headers['Validation-Token']) || isset($headers['validation-token'])) {
    $validationToken = isset($headers['Validation-Token']) 
        ? $headers['Validation-Token'] 
        : $headers['validation-token'];
    
    logTest("Received validation token: $validationToken");
    
    // Echo back the validation token in the response header
    header('Validation-Token: ' . $validationToken);
    
    $response['success'] = true;
    $response['message'] = 'Validation token echoed back successfully';
    echo json_encode($response);
    exit;
}

// Process the webhook payload if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logTest("POST request received");
    
    // Get the raw payload
    $payload = file_get_contents('php://input');
    logTest("Payload: $payload");
    
    if (!empty($payload)) {
        try {
            // Decode the JSON payload
            $data = json_decode($payload, true);
            
            if ($data === null) {
                logTest("Failed to decode JSON payload", "ERROR");
                $response['message'] = 'Invalid JSON payload';
            } else {
                // Successfully processed the webhook payload
                logTest("Successfully processed webhook payload: " . json_encode($data));
                
                // Try to store message in database for testing
                if (isset($data['body']['text']) && isset($data['body']['creatorId'])) {
                    $message = $data['body']['text'];
                    $creatorId = $data['body']['creatorId'];
                    $sessionId = 'test-session-' . time();
                    
                    try {
                        // Create a test message entry in the database
                        $stmt = $db->prepare("INSERT INTO chat_messages 
                            (session_id, message, sender_type, sender_id, ringcentral_message_id) 
                            VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$sessionId, $message, 'agent', $creatorId, 'test-msg-' . time()]);
                        
                        logTest("Test message stored in database with session ID: $sessionId");
                        $response['test_session_id'] = $sessionId;
                    } catch (Exception $e) {
                        logTest("Failed to store test message: " . $e->getMessage(), "ERROR");
                    }
                }
                
                $response['success'] = true;
                $response['message'] = 'Webhook processed successfully';
            }
        } catch (Exception $e) {
            logTest("Exception processing webhook: " . $e->getMessage(), "ERROR");
            $response['message'] = 'Exception: ' . $e->getMessage();
        }
    } else {
        logTest("Empty payload received", "WARNING");
        $response['message'] = 'Empty payload';
    }
} else {
    // For non-POST requests, just show a test page
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>RingCentral Webhook Test</h1>
    <div>
        <h2>Validation Test</h2>
        <p>Send a request with a <code>Validation-Token</code> header to test validation token echo.</p>
        <pre>curl -X GET "' . $_SERVER['REQUEST_URI'] . '" -H "Validation-Token: test-token"</pre>
        
        <h2>Webhook Processing Test</h2>
        <p>Send a POST request with a sample RingCentral payload:</p>
        <pre>curl -X POST "' . $_SERVER['REQUEST_URI'] . '" -H "Content-Type: application/json" -d \'{"body":{"text":"Test message","creatorId":"12345"}}\'</pre>
        
        <h2>Check Logs</h2>
        <p>View the webhook_test.log file for detailed logging.</p>
    </div>
</body>
</html>';
    exit;
}

// Return the final response
echo json_encode($response);
