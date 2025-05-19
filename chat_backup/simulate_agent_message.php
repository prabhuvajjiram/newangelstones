<?php
/**
 * Simulate Agent Message
 * 
 * This script directly inserts a message into the database as if it came from an agent
 * through RingCentral. This helps test the message receiving functionality.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Get request parameters
$sessionId = $_GET['session_id'] ?? '';
$message = $_GET['message'] ?? 'This is a test response from an agent at ' . date('H:i:s');
$senderId = $_GET['sender_id'] ?? '12345678';

if (empty($sessionId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameter: session_id'
    ]);
    exit;
}

try {
    // Get database connection
    $db = getDb();
    
    // Get dynamic column names
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // Check if session exists
    $stmt = $db->prepare("SELECT id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Chat session not found'
        ]);
        exit;
    }
    
    // Generate a unique message ID
    $messageId = 'sim_' . uniqid();
    
    // Insert message into database
    $stmt = $db->prepare("INSERT INTO chat_messages 
                         ($sessionIdColumn, sender_type, message, ring_central_message_id, sender_id, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $sessionId,
        'agent',  // This is crucial - it must be 'agent' to simulate a RingCentral agent message
        $message,
        $messageId,
        $senderId
    ]);
    
    $insertedId = $db->lastInsertId();
    
    // Fetch the inserted message to confirm
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE id = ?");
    $stmt->execute([$insertedId]);
    $insertedMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Agent message simulated successfully',
        'data' => [
            'message_id' => $messageId,
            'session_id' => $sessionId,
            'inserted_id' => $insertedId,
            'message' => $insertedMessage
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
