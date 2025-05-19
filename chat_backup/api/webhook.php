<?php
/**
 * Webhook endpoint for RingCentral
 * 
 * This handles incoming messages from RingCentral and routes them to the appropriate visitor session
 */

// Define entry point constants for secure inclusion
define('LOCAL_ENTRY_POINT', true);
define('WEBHOOK_ENTRY_POINT', true);

// Include configuration and database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

// Set up logging
$logFile = __DIR__ . '/../webhook.log';

function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

// Log the webhook call
logMessage('Webhook received');

// CRITICAL: Handle RingCentral validation token first (required for webhook creation)
$validationToken = $_SERVER['HTTP_VALIDATION_TOKEN'] ?? null;
if (!empty($validationToken)) {
    logMessage('Validation request received with token: ' . $validationToken, 'INFO');
    header("Validation-Token: {$validationToken}");
    http_response_code(200);
    exit; // Exit after returning the validation token, no further processing needed
}

// Get the raw POST data
$postData = file_get_contents('php://input');
logMessage('Raw data: ' . $postData);

// Log headers for debugging
logMessage('Headers: ' . json_encode(getallheaders()));
logMessage('Request Method: ' . $_SERVER['REQUEST_METHOD']);

// Validate webhook signature (if your webhook is configured for validation)
$validationEnabled = false; // Set to true when signature validation is set up

if ($validationEnabled) {
    $signature = $_SERVER['HTTP_X_RINGCENTRAL_SIGNATURE'] ?? null;
    if (!$signature || !validateSignature($postData, $signature)) {
        logMessage('Invalid webhook signature', 'ERROR');
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }
}

// Create status response file to monitor webhook activity
file_put_contents(__DIR__ . '/../webhook_status.json', json_encode([
    'last_request' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'has_data' => !empty($postData),
    'data_preview' => substr($postData, 0, 100)
], JSON_PRETTY_PRINT));

try {
    // Parse the JSON data
    $data = json_decode($postData, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }
    
    logMessage('Parsed data: ' . json_encode($data, JSON_PRETTY_PRINT));
    
    // Connect to database
    $db = getDb();
    
    // Process the webhook data
    processWebhook($db, $data);
    
    // Return success response
    header('HTTP/1.1 200 OK');
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook received and processed'
    ]);
    
} catch (Exception $e) {
    // Log error
    logMessage('Error processing webhook: ' . $e->getMessage(), 'ERROR');
    logError('Webhook Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    
    // Return error response
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to process webhook: ' . $e->getMessage()
    ]);
}

/**
 * Process the webhook data from RingCentral
 */
function processWebhook($db, $data) {
    // Check webhook type - the eventType is inside body, not at the top level
    $eventType = $data['body']['eventType'] ?? null;
    
    logMessage("Processing webhook of type: $eventType");
    
    // Handle different event types
    switch ($eventType) {
        case 'PostAdded':
            handleNewPost($db, $data);
            break;
            
        case 'GroupLeft':
        case 'GroupDeleted':
            // Handle when someone leaves a chat or chat is deleted
            handleChatClosed($db, $data);
            break;
            
        default:
            logMessage("Unhandled event type: $eventType", 'WARN');
            break;
    }
}

/**
 * Handle new message posts
 */
function handleNewPost($db, $data) {
    // Extract the relevant information
    $body = $data['body'] ?? [];
    $messageText = $body['text'] ?? '';
    $messageId = $body['id'] ?? null;
    $chatId = $body['groupId'] ?? null;
    $creatorId = $body['creatorId'] ?? null;
    
    // Log all incoming data for debugging
    logMessage("Handling new post from RingCentral. Message ID: {$messageId}, Chat ID: {$chatId}");
    logMessage("Message content: " . substr($messageText, 0, 200));
    logMessage("Full webhook data: " . json_encode($data, JSON_PRETTY_PRINT));
    $creationTime = $body['creationTime'] ?? date('Y-m-d H:i:s');
    
    if (!$messageId || !$chatId || !$creatorId) {
        logMessage('Missing required message information', 'ERROR');
        return false;
    }
    
    // Check if this is our own message (from the system)
    if (isSystemMessage($creatorId, $messageText)) {
        logMessage('Ignoring our own message', 'INFO');
        return true; // It's our own message, ignore it
    }
    
    // Find the session associated with this chat
    $sessionId = findSessionByChatId($db, $chatId);
    
    // If no specific session found, check if this is the main triage chat
    if (!$sessionId && $chatId === RINGCENTRAL_TEAM_CHAT_ID) {
        // Parse the message to get the session ID
        // Messages in the triage channel should follow a format like [Session: abc123] Message content
        if (preg_match('/\[Session:\s*([a-zA-Z0-9_]+)\]/', $messageText, $matches)) {
            $sessionId = $matches[1];
            logMessage("Extracted session ID $sessionId from triage message");
        } else {
            logMessage("Could not extract session ID from triage message", 'WARN');
            return false;
        }
    }
    
    if (!$sessionId) {
        logMessage("No session ID found for chat $chatId", 'ERROR');
        return false;
    }
    
    // Check if the session exists
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $stmt = $db->prepare("SELECT id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        logMessage("Session $sessionId not found", 'ERROR');
        return false;
    }
    
    // If session is closed, ignore the message
    if ($session['status'] === 'closed') {
        logMessage("Ignoring message for closed session $sessionId", 'INFO');
        return false;
    }
    
    // Store the message using dynamic column handling
    $messageId = storeMessageDynamic($db, $sessionId, $messageText, 'agent', $creatorId, $messageId);
    
    if ($messageId) {
        logMessage("Stored agent message $messageId for session $sessionId");
        logMessage("Message content: " . substr($messageText, 0, 100) . (strlen($messageText) > 100 ? '...' : ''));
        
        // Update the session's last message time
        $stmt = $db->prepare("UPDATE chat_sessions 
                           SET last_message_time = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                           WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
        
        return true;
    } else {
        logMessage("Failed to store agent message for session $sessionId", 'ERROR');
        return false;
    }
}

/**
 * Handle chat closure events
 */
function handleChatClosed($db, $data) {
    $chatId = $data['body']['id'] ?? null;
    
    if (!$chatId) {
        logMessage('Missing chat ID in closure event', 'ERROR');
        return false;
    }
    
    // Find the session associated with this chat
    $sessionId = findSessionByChatId($db, $chatId);
    
    if (!$sessionId) {
        logMessage("No session found for chat $chatId", 'WARN');
        return false;
    }
    
    // Close the session
    $result = closeSession($db, $sessionId);
    
    if ($result) {
        logMessage("Closed session $sessionId due to chat closure");
    } else {
        logMessage("Failed to close session $sessionId", 'ERROR');
    }
    
    return $result;
}

/**
 * Check if a message was sent by our system
 * 
 * This function uses two methods to detect system messages:
 * 1. Check if the message contains our system message flag
 * 2. Check if the creator ID is in our list of system users
 */
function isSystemMessage($creatorId, $messageText = '') {
    // Method 1: Check for system message flag in the message text
    if (!empty($messageText) && strpos($messageText, SYSTEM_MESSAGE_FLAG) !== false) {
        logMessage('Message identified as system message via flag');
        return true;
    }
    
    // Method 2: Check if the creator is in our list of system users
    $systemUsers = json_decode(SYSTEM_USERS, true);
    if (in_array($creatorId, $systemUsers)) {
        logMessage('Message identified as system message via creator ID');
        return true;
    }
    
    return false;
}

/**
 * Validate webhook signature
 */
function validateSignature($payload, $signature) {
    // This is a placeholder - implement proper signature validation based on RingCentral's requirements
    // Typically involves computing HMAC using a shared secret
    return true;
}
?>
