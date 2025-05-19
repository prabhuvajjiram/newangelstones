<?php
/**
 * Link Orphaned Messages
 * 
 * This script finds and links any agent messages that don't have a session ID
 * to the appropriate active session based on Ring Central chat ID
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Output format
header('Content-Type: application/json');

try {
    // Get database connection
    $db = getDb();
    
    // Get dynamic column names for sessions table
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // Get dynamic column names for messages table
    $msgSessionIdColumn = getDynamicColumnName($db, 'chat_messages', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $msgRcIdColumn = getDynamicColumnName($db, 'chat_messages', ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'message_id']);
    
    // Find orphaned agent messages (messages with no session ID or a NULL session ID)
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE sender_type = 'agent' AND ($msgSessionIdColumn IS NULL OR $msgSessionIdColumn = '')");
    $stmt->execute();
    $orphanedMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $linked = [];
    $sessionCache = [];
    
    // Find active sessions
    $stmt = $db->prepare("SELECT id, $sessionIdColumn, $chatIdColumn FROM chat_sessions WHERE status = 'active'");
    $stmt->execute();
    $activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cache sessions by chat ID for quick lookup
    foreach ($activeSessions as $session) {
        if (!empty($session[$chatIdColumn])) {
            $sessionCache[$session[$chatIdColumn]] = $session[$sessionIdColumn];
        }
    }
    
    // Get the default Ring Central chat ID
    $defaultChatId = defined('RINGCENTRAL_TEAM_CHAT_ID') ? RINGCENTRAL_TEAM_CHAT_ID : 
                    (defined('RINGCENTRAL_DEFAULT_CHAT_ID') ? RINGCENTRAL_DEFAULT_CHAT_ID : '147193044998');
    
    // Link each orphaned message to the right session
    foreach ($orphanedMessages as $message) {
        // If we have active session for this chat ID, use that
        $targetSessionId = $sessionCache[$defaultChatId] ?? null;
        
        if ($targetSessionId) {
            // Update the message with the session ID
            $stmt = $db->prepare("UPDATE chat_messages SET $msgSessionIdColumn = ? WHERE id = ?");
            $stmt->execute([$targetSessionId, $message['id']]);
            $linked[] = [
                'message_id' => $message['id'],
                'session_id' => $targetSessionId,
                'message' => substr($message['message'], 0, 30) . '...'
            ];
        }
    }
    
    // Output results
    echo json_encode([
        'status' => 'success',
        'message' => count($linked) . ' orphaned messages linked to active sessions',
        'linked_messages' => $linked,
        'default_chat_id' => $defaultChatId,
        'active_sessions' => array_values($sessionCache)
    ]);
    
} catch (Exception $e) {
    // Handle errors
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
