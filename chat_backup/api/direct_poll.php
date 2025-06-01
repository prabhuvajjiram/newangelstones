<?php
/**
 * Direct Poll API - Simplified approach based on troubleshoot_chat.php
 *
 * This simplified polling mechanism gets ALL agent messages and filters them on the server side
 * to match the approach used in troubleshoot_chat.php which works successfully
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS requests (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log function
function logToFile($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../debug_poll.log';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

try {
    // Get request parameters
    $sessionId = $_GET['session_id'] ?? '';
    $lastMessageId = $_GET['last_message_id'] ?? 0;
    
    if (empty($sessionId)) {
        throw new Exception("Session ID is required");
    }
    
    logToFile("Polling for session: $sessionId, last message ID: $lastMessageId");
    
    // Get database connection
    $db = getDb();
    
    // Get column names
    $senderTypeColumn = getDynamicColumnName($db, 'chat_messages', ['sender_type', 'type', 'message_type', 'msg_type']);
    $messageSessionIdColumn = getDynamicColumnName($db, 'chat_messages', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // STEP 1: Get session-specific visitor messages first (normal approach)
    $visitorMessages = [];
    $query = "SELECT * FROM chat_messages 
             WHERE $messageSessionIdColumn = ? 
             AND ($senderTypeColumn = 'visitor' OR $senderTypeColumn = 'system')";
             
    if ($lastMessageId > 0) {
        $query .= " AND id > ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$sessionId, $lastMessageId]);
    } else {
        $stmt = $db->prepare($query);
        $stmt->execute([$sessionId]);
    }
    
    $visitorMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logToFile("Found " . count($visitorMessages) . " visitor/system messages for session: $sessionId");
    
    // STEP 2: Get ALL recent agent messages - mirroring the approach in troubleshoot_chat.php
    $agentMessagesQuery = "SELECT * FROM chat_messages 
                         WHERE $senderTypeColumn = 'agent'
                         ORDER BY created_at DESC LIMIT 25";
    
    $stmt = $db->prepare($agentMessagesQuery);
    $stmt->execute();
    $allAgentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logToFile("Found " . count($allAgentMessages) . " total agent messages");
    
    // STEP 3: Filter agent messages to show most recent ones
    // This is similar to how the troubleshoot tool displays them
    $agentMessages = [];
    $processedMsgIds = [];
    
    foreach ($allAgentMessages as $msg) {
        // Skip messages we've already seen
        if ($lastMessageId > 0 && $msg['id'] <= $lastMessageId) {
            continue;
        }
        
        // Skip duplicates
        if (in_array($msg['id'], $processedMsgIds)) {
            continue;
        }
        
        // Include this message
        $agentMessages[] = $msg;
        $processedMsgIds[] = $msg['id'];
        
        // Opportunistically update this message to associate it with the session
        // if it doesn't already have a session ID
        if (empty($msg[$messageSessionIdColumn])) {
            $updateStmt = $db->prepare("UPDATE chat_messages SET $messageSessionIdColumn = ? WHERE id = ?");
            $updateStmt->execute([$sessionId, $msg['id']]);
            logToFile("Associated agent message #{$msg['id']} with session $sessionId");
        }
    }
    
    logToFile("Selected " . count($agentMessages) . " relevant agent messages to display");
    
    // STEP 4: Combine all messages and sort by timestamp
    $combinedMessages = array_merge($visitorMessages, $agentMessages);
    
    // Sort by created_at
    usort($combinedMessages, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    // Return response
    echo json_encode([
        'status' => 'success',
        'session_id' => $sessionId,
        'messages' => $combinedMessages,
        'count' => count($combinedMessages),
        'visitor_count' => count($visitorMessages),
        'agent_count' => count($agentMessages),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    logToFile("Error: " . $e->getMessage(), 'ERROR');
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
