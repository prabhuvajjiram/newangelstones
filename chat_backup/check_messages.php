<?php
/**
 * Check Messages Tool
 * 
 * This script checks the database for messages in a specific chat session
 * to help debug issues with RingCentral integration.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Set content type to JSON for API-like responses
header('Content-Type: application/json');

// Get parameters
$sessionId = $_GET['session_id'] ?? '';
$limit = $_GET['limit'] ?? 50;

if (empty($sessionId)) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Missing session_id parameter'
    ]));
}

try {
    // Connect to database
    $db = getDb();
    
    // Get dynamic column names
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    
    // Check if session exists
    $stmt = $db->prepare("SELECT id, $chatIdColumn as chat_id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        die(json_encode([
            'status' => 'error',
            'message' => "Session $sessionId not found in database"
        ]));
    }
    
    // Get messages from database
    $messagesTable = 'chat_messages';
    $query = "SELECT * FROM $messagesTable WHERE $sessionIdColumn = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$sessionId, $limit]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count messages by type
    $counts = [
        'visitor' => 0,
        'agent' => 0,
        'system' => 0,
        'total' => count($messages)
    ];
    
    foreach ($messages as $msg) {
        $type = $msg['sender_type'] ?? 'unknown';
        if (isset($counts[$type])) {
            $counts[$type]++;
        }
    }
    
    // Return results
    echo json_encode([
        'status' => 'success',
        'session' => [
            'id' => $session['id'],
            'session_id' => $sessionId,
            'chat_id' => $session['chat_id'],
            'status' => $session['status']
        ],
        'message_counts' => $counts,
        'messages' => $messages,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
