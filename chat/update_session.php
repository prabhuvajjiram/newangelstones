<?php
/**
 * Update Session with Chat ID
 * 
 * This script updates a chat session with the correct RingCentral chat ID
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Get session ID from URL parameter
$sessionId = $_GET['session_id'] ?? '';

// Set content type to HTML for browser viewing
header('Content-Type: text/html; charset=utf-8');
echo '<h1>Update Chat Session</h1>';

if (empty($sessionId)) {
    echo '<div style="color: red; font-weight: bold;">ERROR: Missing session_id parameter</div>';
    echo '<p>Use ?session_id=YOUR_SESSION_ID in the URL</p>';
    exit;
}

try {
    // Get database connection
    $db = getDb();
    
    // Get dynamic column names
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    
    // Check if session exists
    $stmt = $db->prepare("SELECT id, $chatIdColumn as chat_id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo '<div style="color: red; font-weight: bold;">ERROR: Session not found in database</div>';
        exit;
    }
    
    echo '<p>Current session details:</p>';
    echo '<pre>' . print_r($session, true) . '</pre>';
    
    // Update with the correct chat ID
    $chatId = RINGCENTRAL_TEAM_CHAT_ID;
    
    $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE $sessionIdColumn = ?");
    $result = $stmt->execute([$chatId, $sessionId]);
    
    if ($result) {
        echo '<div style="color: green; font-weight: bold;">✓ Successfully updated session with chat ID: ' . $chatId . '</div>';
        
        // Verify the update
        $stmt = $db->prepare("SELECT id, $chatIdColumn as chat_id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
        $updatedSession = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo '<p>Updated session details:</p>';
        echo '<pre>' . print_r($updatedSession, true) . '</pre>';
        
        echo '<p><a href="test_chat.html">Back to Test Chat</a></p>';
        echo '<p><a href="debug_polling.php?session_id=' . urlencode($sessionId) . '">Back to Debug Tool</a></p>';
    } else {
        echo '<div style="color: red; font-weight: bold;">ERROR: Failed to update session</div>';
    }
    
} catch (Exception $e) {
    echo '<div style="color: red; font-weight: bold;">ERROR: ' . $e->getMessage() . '</div>';
}
?>
