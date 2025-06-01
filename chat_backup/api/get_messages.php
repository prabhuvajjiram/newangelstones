<?php
/**
 * RingCentral Chat Get Messages API
 * 
 * Retrieves messages for a chat session
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

// Simple logging function
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../ringcentral_chat.log';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Get parameters
    $sessionId = $_GET['session_id'] ?? '';
    $since = $_GET['since'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    logMessage("get_messages.php called for session: $sessionId");
    
    if (empty($sessionId)) {
        throw new Exception("Missing required parameter: session_id");
    }
    
    // Get database connection
    $db = getDb();
    
    // Get messages using the dynamic column detection
    $messages = getChatMessages($db, $sessionId, $limit, $offset);
    
    // Check if we need to filter by timestamp
    if (!empty($since) && !empty($messages)) {
        $filteredMessages = [];
        foreach ($messages as $message) {
            if (strtotime($message['created_at']) > strtotime($since)) {
                $filteredMessages[] = $message;
            }
        }
        $messages = $filteredMessages;
    }
    
    // Format messages for the client
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessages[] = [
            'id' => $message['id'],
            'sender_type' => $message['sender_type'],
            'message' => $message['message'],
            'timestamp' => $message['created_at'],
            'metadata' => isset($message['metadata']) ? json_decode($message['metadata'], true) : null
        ];
    }
    
    logMessage("Retrieved " . count($formattedMessages) . " messages for session: $sessionId");
    
    // Get session information
    $sessionInfo = [];
    
    try {
        $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
        $stmt = $db->prepare("SELECT * FROM chat_sessions WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
        $sessionInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        logMessage("Error getting session info: " . $e->getMessage(), 'WARN');
    }
    
    // Return messages
    echo json_encode([
        'status' => 'success',
        'session_id' => $sessionId,
        'session_info' => $sessionInfo,
        'messages' => $formattedMessages,
        'count' => count($formattedMessages),
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    // Log error
    logError("Error in get_messages.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}
?>
