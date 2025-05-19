<?php
/**
 * Auto-link Session Utility
 * 
 * Automatically links all active chat sessions to the RingCentral team chat ID
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
    
    // Get dynamic column names
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // Get the default Ring Central chat ID
    $defaultChatId = defined('RINGCENTRAL_TEAM_CHAT_ID') ? RINGCENTRAL_TEAM_CHAT_ID : 
                    (defined('RINGCENTRAL_DEFAULT_CHAT_ID') ? RINGCENTRAL_DEFAULT_CHAT_ID : '147193044998');
    
    // Find all active sessions without a chat ID
    $stmt = $db->prepare("SELECT id, $sessionIdColumn FROM chat_sessions WHERE status = 'active' AND ($chatIdColumn IS NULL OR $chatIdColumn = '')");
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = [];
    
    // Update each session
    foreach ($sessions as $session) {
        $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE id = ?");
        $stmt->execute([$defaultChatId, $session['id']]);
        $updated[] = $session[$sessionIdColumn];
    }
    
    // Output results
    echo json_encode([
        'status' => 'success',
        'message' => count($updated) . ' sessions linked to RingCentral chat ID',
        'updated_sessions' => $updated,
        'chat_id' => $defaultChatId
    ]);
    
} catch (Exception $e) {
    // Handle errors
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
