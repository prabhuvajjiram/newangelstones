<?php
/**
 * RingCentral Chat Message Polling API
 * 
 * Polls for new messages in a chat session, used for real-time updates
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

// Simple logging function if not already defined
if (!function_exists('logMessage')) {
    function logMessage($message, $level = 'INFO') {
        $logFile = __DIR__ . '/../ringcentral_chat.log';
        $timestamp = date('[Y-m-d H:i:s] ');
        file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
}

try {
    // Extract parameters
    $sessionId = $_GET['session_id'] ?? '';
    $lastMessageId = $_GET['last_message_id'] ?? 0;
    $lastTimestamp = $_GET['last_timestamp'] ?? '';
    
    if (empty($sessionId)) {
        throw new Exception("Missing required parameter: session_id");
    }
    
    // Get database connection
    $db = getDb();
    
    // Get dynamic column names
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // First check if session exists
    $stmt = $db->prepare("SELECT status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        // Instead of throwing an error, auto-create the session
        logMessage("Auto-creating session: $sessionId", 'INFO');
        
        try {
            // Insert a new session record
            $stmt = $db->prepare("INSERT INTO chat_sessions 
                ($sessionIdColumn, status, created_at, updated_at, last_message_time) 
                VALUES (?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([$sessionId]);
            
            // Return the newly created session
            $session = ['status' => 'active'];
            
            logMessage("Session $sessionId created successfully", 'INFO');
        } catch (Exception $e) {
            logMessage("Failed to create session: $sessionId - " . $e->getMessage(), 'ERROR');
            throw new Exception("Failed to create chat session");
        }
    }
    
    // Build query based on the filter provided (last message ID or timestamp)
    $params = [$sessionId];
    $where = '';
    
    if (!empty($lastMessageId) && $lastMessageId > 0) {
        $where = " AND id > ?";
        $params[] = $lastMessageId;
    } else if (!empty($lastTimestamp)) {
        $where = " AND created_at > ?";
        $params[] = $lastTimestamp;
    }
    
    // Log polling details
    logMessage("Polling for messages with session ID: $sessionId, last message ID: $lastMessageId, last timestamp: $lastTimestamp");
    
    // Get the session details to check if we need to find by chat ID as well
    $sessionQuery = "SELECT * FROM chat_sessions WHERE $sessionIdColumn = ?";
    $sessionStmt = $db->prepare($sessionQuery);
    $sessionStmt->execute([$sessionId]);
    $sessionDetails = $sessionStmt->fetch(PDO::FETCH_ASSOC);
    
    // Find Ring Central chat ID column
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    $rcChatId = $sessionDetails[$chatIdColumn] ?? null;
    
    // Get message table column names
    $msgSessionIdColumn = getDynamicColumnName($db, 'chat_messages', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    logMessage("Session details: ID=$sessionId, RingCentral Chat ID=$rcChatId");
    
    // Get ALL messages for this session - agent messages may be in the database but not linked to this session
    // This simplified query matches what works in troubleshoot_chat.php
    $messagesQuery = "SELECT * FROM chat_messages 
                     WHERE $msgSessionIdColumn = ? OR 
                           (sender_type = 'agent' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR))
                     ORDER BY created_at ASC LIMIT 100";
    
    // Execute query
    $stmt = $db->prepare($messagesQuery);
    $stmt->execute([$sessionId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($messages) . " regular messages for session $sessionId");
    
    // QUERY 2: Also check specifically for agent messages that belong to this chat but aren't linked to the session
    // This is the key fix: Look for agent messages for this RingCentral chat ID but not linked to any session
    if ($rcChatId) {
        // First check if the chat has a ringcental_message_id column to match against
        $rcMsgIdColumn = getDynamicColumnName($db, 'chat_messages', 
            ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'external_id']);
        
        if ($rcMsgIdColumn) {
            logMessage("Looking for agent messages with RingCentral chat ID: $rcChatId");
            
            // Get agent messages for this chat but not linked to this session
            // We have to check if they're greater than the last message ID
            $agentQuery = "SELECT * FROM chat_messages 
                         WHERE sender_type = 'agent' 
                         AND ($msgSessionIdColumn IS NULL OR $msgSessionIdColumn = '') ";
            
            if ($lastMessageId > 0) {
                $agentQuery .= "AND id > ? ";
                $agentStmt = $db->prepare($agentQuery);
                $agentStmt->execute([$lastMessageId]);
            } else {
                $agentStmt = $db->prepare($agentQuery);
                $agentStmt->execute();
            }
            
            $agentMessages = $agentStmt->fetchAll(PDO::FETCH_ASSOC);
            
            logMessage("Found " . count($agentMessages) . " unlinked agent messages");
            
            // Link these messages to the session and add them to our result set
            foreach ($agentMessages as $agentMsg) {
                // Link this message to the session
                $updateStmt = $db->prepare("UPDATE chat_messages SET $msgSessionIdColumn = ? WHERE id = ?");
                $updateStmt->execute([$sessionId, $agentMsg['id']]);
                logMessage("Linked agent message #{$agentMsg['id']} to session $sessionId");
                
                // Add to our result set
                $messages[] = $agentMsg;
            }
        }    
    }
    
    // If we didn't find any messages, also check for agent messages that might have the same RingCentral chat ID
    // but aren't yet linked to this session
    if (count($messages) == 0 && $rcChatId) {
        // Find any recent agent messages that aren't linked to a session
        $agentMsgQuery = "SELECT * FROM chat_messages 
                          WHERE sender_type = 'agent' 
                          AND ($msgSessionIdColumn IS NULL OR $msgSessionIdColumn = '') 
                          ORDER BY created_at DESC 
                          LIMIT 10";
        
        logMessage("Looking for unlinked agent messages");
        $agentStmt = $db->prepare($agentMsgQuery);
        $agentStmt->execute();
        $agentMessages = $agentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we found some, link them to this session and include them
        if (count($agentMessages) > 0) {
            logMessage("Found " . count($agentMessages) . " unlinked agent messages to check");
            
            foreach ($agentMessages as $agentMsg) {
                // Link this message to the session
                $updateStmt = $db->prepare("UPDATE chat_messages SET $msgSessionIdColumn = ? WHERE id = ?");
                $updateStmt->execute([$sessionId, $agentMsg['id']]);
                logMessage("Linked agent message #{$agentMsg['id']} to session $sessionId");
                
                // Add it to our result set
                $messages[] = $agentMsg;
            }
        }
    }
    
    // Log the results
    logMessage("Found " . count($messages) . " messages for session $sessionId");
    
    // Return the messages in the response
    echo json_encode([
        'status' => 'success',
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    // Log the error
    if (function_exists('logError')) {
        logError("Error polling messages: " . $e->getMessage());
    } else if (function_exists('logMessage')) {
        logMessage("Error polling messages: " . $e->getMessage(), 'ERROR');
    }
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>