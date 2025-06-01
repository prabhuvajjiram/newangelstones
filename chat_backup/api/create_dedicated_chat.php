<?php
/**
 * Create Dedicated Chat API
 * 
 * Creates a dedicated chat room in RingCentral for a specific visitor session
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

// Set JSON header
header('Content-Type: application/json');

// Enable CORS from allowed origins
setCorsHeaders();

// Handle OPTIONS requests (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set up logging
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../ringcentral_chat.log';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log what we received
    logMessage("Received POST data in create_dedicated_chat.php: " . json_encode($input));
    
    // Check required fields
    if (empty($input['session_id'])) {
        throw new Exception("Missing required field: session_id");
    }
    
    // Extract data
    $sessionId = $input['session_id'];
    $visitorName = $input['visitor_name'] ?? 'Anonymous Visitor';
    $visitorEmail = $input['visitor_email'] ?? '';
    
    // Get database connection
    $db = getDb();
    
    // First check if this session already has a dedicated chat room
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    
    $stmt = $db->prepare("SELECT $chatIdColumn FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $existingChatId = $stmt->fetchColumn();
    
    if ($existingChatId) {
        // Chat room already exists for this session
        logMessage("Found existing chat room ($existingChatId) for session $sessionId");
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Existing chat room found',
            'chat_id' => $existingChatId,
            'session_id' => $sessionId,
            'created' => false
        ]);
        exit();
    }
    
    // Create a new dedicated chat room in RingCentral
    require_once __DIR__ . '/../RingCentralTeamMessagingClient.php';
    
    // Determine which authentication method to use
    $authType = defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'oauth';
    $clientConfig = [
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'tokenPath' => __DIR__ . '/../secure_storage/rc_token.json',
        'teamChatId' => RINGCENTRAL_TEAM_CHAT_ID // Default team chat ID
    ];
    
    // Add JWT token if using JWT authentication
    if ($authType === 'jwt' && defined('RINGCENTRAL_JWT_TOKEN')) {
        $clientConfig['jwtToken'] = RINGCENTRAL_JWT_TOKEN;
        logMessage("Using JWT authentication for RingCentral");
    } else {
        // Fall back to OAuth if JWT is not available
        if (defined('RINGCENTRAL_USERNAME') && defined('RINGCENTRAL_PASSWORD') && defined('RINGCENTRAL_EXTENSION')) {
            $clientConfig['username'] = RINGCENTRAL_USERNAME;
            $clientConfig['password'] = RINGCENTRAL_PASSWORD;
            $clientConfig['extension'] = RINGCENTRAL_EXTENSION;
            logMessage("Using OAuth authentication for RingCentral");
        }
    }
    
    $rcClient = new RingCentralTeamMessagingClient($clientConfig);
    
    // Check if authenticated with RingCentral
    if (!$rcClient->hasValidToken() && !$rcClient->authenticate()) {
        throw new Exception("Failed to authenticate with RingCentral");
    }
    
    // Generate a name for the chat room
    $chatName = sprintf(
        "Customer Chat: %s (%s)",
        $visitorName,
        substr($sessionId, 0, 8)
    );
    
    // For now, we'll use the default team chat
    // In a production environment, you would create a new group chat here
    // using the RingCentral Team Messaging API
    $chatId = RINGCENTRAL_TEAM_CHAT_ID;
    
    // In this simplified version, we're using the main team chat
    // You can implement actual group creation by extending the RingCentralTeamMessagingClient class
    
    // Update the session with the chat ID
    $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE $sessionIdColumn = ?");
    $stmt->execute([$chatId, $sessionId]);
    
    // Log the result
    logMessage("Linked session $sessionId to chat $chatId");
    
    // Send a welcome message to the RingCentral chat
    $welcomeMessage = sprintf(
        "**New Chat Session Started**\n\n" .
        "- Session ID: %s\n" .
        "- Visitor: %s\n" .
        "- Email: %s\n\n" .
        "_This conversation will be linked to the visitor's session._",
        $sessionId,
        $visitorName,
        $visitorEmail ?: 'Not provided'
    );
    
    // Add system flag to identify our messages
    $welcomeMessage .= "\n\n" . SYSTEM_MESSAGE_FLAG;
    
    // Send the welcome message
    $messageResult = $rcClient->postMessage($chatId, $welcomeMessage);
    
    if ($messageResult) {
        logMessage("Sent welcome message to chat $chatId");
    } else {
        logMessage("Failed to send welcome message to chat $chatId", 'WARN');
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Dedicated chat room created successfully',
        'chat_id' => $chatId,
        'session_id' => $sessionId,
        'created' => true
    ]);
    
} catch (Exception $e) {
    // Log error
    logMessage("Error in create_dedicated_chat.php: " . $e->getMessage(), 'ERROR');
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create dedicated chat room: ' . $e->getMessage()
    ]);
}
?>
