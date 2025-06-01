<?php
/**
 * RingCentral Webhook Handler
 * 
 * Receives webhook notifications from RingCentral when new messages are posted
 * and saves them to the local database.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

// Define log file
$logFile = __DIR__ . '/../webhook.log';

// Logging function
function logWebhook($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

// Set JSON response header
header('Content-Type: application/json');

// Log all incoming webhook requests
logWebhook("Webhook received: " . file_get_contents('php://input'));

try {
    // Read the raw input
    $input = file_get_contents('php://input');
    $webhook = json_decode($input, true);
    
    // Verify the webhook is from RingCentral
    // Ideally, you should implement proper validation here
    
    // Validate webhook validation - if it's a validation request from RingCentral
    if (isset($webhook['validation_token'])) {
        // This is a webhook validation request
        logWebhook("Received validation request from RingCentral", 'INFO');
        
        // Return the validation token to confirm the webhook
        header('Content-Type: text/plain');
        echo $webhook['validation_token'];
        logWebhook("Returned validation token: " . $webhook['validation_token'], 'INFO');
        exit();
    }

    // Check what type of webhook this is based on RingCentral documentation
    // Documentation: https://developers.ringcentral.com/guide/notifications/message-store-notifications
    if (isset($webhook['event']) && strpos($webhook['event'], '/restapi/v1.0/glip/posts') === 0) {
        // This is a subscription API webhook
        logWebhook("Processing Subscription API webhook");
    } else if (isset($webhook['activity']) && isset($webhook['activity']['type']) && $webhook['activity']['type'] === 'PostAdded') {
        // This is a Team Chat webhook
        logWebhook("Processing Team Chat webhook");
    } else if (isset($webhook['uuid']) && isset($webhook['event']) && $webhook['event'] === 'PostAdded') {
        // Another variant of the Glip webhook format
        logWebhook("Processing Glip webhook with uuid");
    } else if (isset($webhook['body']) && isset($webhook['body']['id']) && isset($webhook['body']['text'])) {
        // Simplified RingCentral webhook format
        logWebhook("Processing simplified webhook format");
    } else {
        // Not a recognized message event
        logWebhook("Unrecognized webhook format: " . json_encode($webhook), 'WARN');
        echo json_encode(['status' => 'success', 'message' => 'Webhook received but format not recognized']);
        exit();
    }
    
    // Extract the message data based on webhook format
    if (isset($webhook['event']) && strpos($webhook['event'], '/restapi/v1.0/glip/posts') === 0) {
        // Subscription API webhook format
        $messageData = $webhook['body'];
        $chatId = $messageData['groupId'] ?? null;
        $messageText = $messageData['text'] ?? null;
        $creatorId = $messageData['creatorId'] ?? null;
        $timestamp = isset($messageData['creationTime']) ? date('Y-m-d H:i:s', strtotime($messageData['creationTime'])) : date('Y-m-d H:i:s');
    } else if (isset($webhook['activity']) && isset($webhook['activity']['type']) && $webhook['activity']['type'] === 'PostAdded') {
        // Team Chat webhook format
        $messageData = $webhook['activity']['body'] ?? [];
        $chatId = $messageData['groupId'] ?? null;
        $messageText = $messageData['text'] ?? null;
        $creatorId = $messageData['creatorId'] ?? null;
        $timestamp = isset($messageData['creationTime']) ? date('Y-m-d H:i:s', strtotime($messageData['creationTime'])) : date('Y-m-d H:i:s');
    } else if (isset($webhook['uuid']) && isset($webhook['event']) && $webhook['event'] === 'PostAdded') {
        // Another variant of the Glip webhook format
        $messageData = $webhook['body'] ?? [];
        $chatId = $messageData['groupId'] ?? $webhook['conversation']['id'] ?? null;
        $messageText = $messageData['text'] ?? $webhook['text'] ?? null;
        $creatorId = $messageData['creatorId'] ?? $webhook['creator']['id'] ?? null;
        $timestamp = date('Y-m-d H:i:s');
    } else if (isset($webhook['body']) && isset($webhook['body']['id']) && isset($webhook['body']['text'])) {
        // Simplified RingCentral webhook format
        $messageData = $webhook['body'];
        $chatId = $messageData['groupId'] ?? $webhook['conversation'] ?? RINGCENTRAL_TEAM_CHAT_ID;
        $messageText = $messageData['text'];
        $creatorId = $messageData['creatorId'] ?? $messageData['creator'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');
    } else {
        // Unknown format, try to extract from raw data
        logWebhook("Trying to extract from unknown format: " . json_encode($webhook), 'WARN');
        
        // Look for common fields in the JSON structure with deep traversal
        $chatId = null;
        $messageText = null;
        $creatorId = null;
        
        // Function to recursively search for keys in an array
        $searchInArray = function($array, $keys) use (&$searchInArray) {
            $result = [];
            foreach ($keys as $key) {
                if (isset($array[$key])) {
                    $result[$key] = $array[$key];
                }
            }
            
            if (count($result) === count($keys)) {
                return $result;
            }
            
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    $found = $searchInArray($v, $keys);
                    if (count($found) === count($keys)) {
                        return $found;
                    }
                    foreach ($found as $fk => $fv) {
                        $result[$fk] = $fv;
                    }
                }
            }
            
            return $result;
        };
        
        $searchResult = $searchInArray($webhook, ['groupId', 'text', 'creatorId']);
        
        $chatId = $searchResult['groupId'] ?? $webhook['groupId'] ?? $webhook['chatId'] ?? $webhook['conversation'] ?? $webhook['group'] ?? RINGCENTRAL_TEAM_CHAT_ID;
        $messageText = $searchResult['text'] ?? $webhook['text'] ?? $webhook['message'] ?? $webhook['content'] ?? 'No message content found';
        $creatorId = $searchResult['creatorId'] ?? $webhook['creatorId'] ?? $webhook['creator'] ?? $webhook['sender'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');
        
        logWebhook("Extracted data from unknown format: chatId=$chatId, messageText=$messageText, creatorId=$creatorId");
    }
    
    // Make sure we have the minimum required data
    if (empty($chatId) || empty($messageText)) {
        logWebhook("Missing required data: chatId or messageText", 'ERROR');
        echo json_encode(['status' => 'error', 'message' => 'Invalid message format - missing required fields']);
        exit();
    }
    
    // Variables have been extracted in the previous code block
    
    // Check if this is sent by a bot or an agent
    // Bot messages typically have certain IDs or markers
    // For simplicity, we'll consider all external messages as agent messages
    // unless they match our client ID (which would indicate our own bot)
    $isAgent = true;
    
    // Skip saving our own messages that were sent from the chat interface
    // to avoid duplicate messages in the chat
    if (strpos($messageText, "**New Message from Website Visitor**") !== false) {
        logWebhook("Skipping our own outgoing message");
        echo json_encode(['status' => 'success', 'message' => 'Outgoing message received, not processing']);
        exit();
    }
    
    // Skip any messages from our own bot/client
    if (isset($messageData['creatorId']) && $messageData['creatorId'] === RINGCENTRAL_BOT_ID) {
        $isAgent = false;
        logWebhook("Message appears to be from our own bot, skipping");
        echo json_encode(['status' => 'success', 'message' => 'Bot message received, not processing']);
        exit();
    }
    
    // Only process agent messages (we don't want to duplicate our own messages)
    if (!$isAgent) {
        logWebhook("Skipping message from our own client/bot");
        echo json_encode(['status' => 'success', 'message' => 'Message recognized as our own, not processing']);
        exit();
    }
    
    // Get database connection
    $db = getDb();
    
    // First try to extract session ID from message text - this is more accurate for team chat
    $sessionId = null;
    
    // Log the message text to help debug
    logWebhook("Attempting to extract session ID from message: " . substr($messageText, 0, 100) . "...");
    
    // Try multi-line pattern first (most reliable for formatted messages)
    if (preg_match('/\*\*Session ID:\*\*\s+(session_[a-zA-Z0-9]+)/i', $messageText, $matches)) {
        $sessionId = $matches[1];
        logWebhook("Extracted session ID from formatted message: $sessionId");
    }
    // Try other patterns if first one failed
    else {
        $sessionIdPatterns = [
            '/Session ID:?\s*(session_[a-zA-Z0-9]+)/i',      // Look for "Session ID: session_abc123"
            '/session_[a-zA-Z0-9]+/i',                      // Look for any session_abc123 in text
            '/session[-_\s]([a-zA-Z0-9]+)/i'                // Look for session followed by ID
        ];
        
        foreach ($sessionIdPatterns as $pattern) {
            if (preg_match($pattern, $messageText, $matches)) {
                // If the pattern matched the whole ID including 'session_' prefix
                if (strpos($matches[0], 'session_') === 0) {
                    $sessionId = $matches[0];
                }
                // If the pattern matched just the ID part
                else if (isset($matches[1])) {
                    $sessionId = 'session_' . $matches[1];
                }
                
                logWebhook("Extracted session ID using pattern $pattern: $sessionId");
                break;
            }
        }
    }
    
    // If we're looking at a new webhook, check if we're already processing this message ID
    // This helps prevent duplication from multiple webhook deliveries
    if (isset($messageData['id'])) {
        $messageExternalId = $messageData['id'];
        $processingKey = "processing_" . $messageExternalId;
        $processingFile = __DIR__ . "/../tmp/" . $processingKey;
        
        // Create tmp directory if it doesn't exist
        if (!file_exists(__DIR__ . "/../tmp/")) {
            mkdir(__DIR__ . "/../tmp/", 0755, true);
        }
        
        // Check if this message is already being processed
        if (file_exists($processingFile)) {
            $timeDiff = time() - filemtime($processingFile);
            if ($timeDiff < 60) { // If file was created less than 60 seconds ago
                logWebhook("Duplicate webhook delivery detected for message ID $messageExternalId");
                echo json_encode(['status' => 'success', 'message' => 'Duplicate webhook delivery, skipping']);
                exit();
            }
        }
        
        // Mark this message as being processed
        file_put_contents($processingFile, date('Y-m-d H:i:s'));
    }
    
    // If session ID wasn't found in the message text, try to look it up by chat ID
    if (!$sessionId) {
        try {
            // Get database connection
            $db = getDb();
            
            // Try to find the session ID associated with this chat ID
            $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
            $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
            
            logWebhook("Looking for session with chat ID $chatId using columns $sessionIdColumn and $chatIdColumn");
            
            // Try an exact match first
            $stmt = $db->prepare("SELECT $sessionIdColumn FROM chat_sessions WHERE $chatIdColumn = ? ORDER BY last_message_time DESC LIMIT 1");
            $stmt->execute([$chatId]);
            $sessionId = $stmt->fetchColumn();
            
            if ($sessionId) {
                logWebhook("Found session ID from database using exact match: $sessionId");
            } else {
                // Try a partial match (some chat IDs might be stored with or without quotes)
                $stmt = $db->prepare("SELECT $sessionIdColumn, $chatIdColumn FROM chat_sessions WHERE $chatIdColumn LIKE ? ORDER BY last_message_time DESC LIMIT 1");
                $stmt->execute(['%' . $chatId . '%']);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $sessionId = $result[$sessionIdColumn];
                    logWebhook("Found session ID from database using partial match: $sessionId (stored chat ID: {$result[$chatIdColumn]})");
                }
            }
        } catch (Exception $e) {
            logWebhook("Error looking up session ID: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // If we still don't have a session ID, try to find the most recent session in the database
    if (!$sessionId) {
        try {
            // First, try to find ANY active session - this is the most important fix
            // We just need any active session to display the agent message to the user
            $stmt = $db->prepare("SELECT $sessionIdColumn, last_message_time FROM chat_sessions WHERE status = 'active' ORDER BY last_message_time DESC LIMIT 5");
            $stmt->execute();
            $activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($activeSessions)) {
                // Use the most recent active session
                $sessionId = $activeSessions[0][$sessionIdColumn];
                logWebhook("Using most recent active session: $sessionId (last activity: {$activeSessions[0]['last_message_time']})");
                
                // If we have multiple active sessions, log them for debugging
                if (count($activeSessions) > 1) {
                    $otherSessions = array_slice($activeSessions, 1, 4);
                    $sessionList = '';
                    foreach ($otherSessions as $session) {
                        $sessionList .= $session[$sessionIdColumn] . ' (last: ' . $session['last_message_time'] . '), ';
                    }
                    logWebhook("Other active sessions: $sessionList");
                }
                
                // Update this session to associate it with this chat ID for future messages
                $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE $sessionIdColumn = ?");
                $stmt->execute([$chatId, $sessionId]);
                logWebhook("Updated session $sessionId to associate with chat ID $chatId");
            } else {
                // No active sessions found, try to find any session linked to this chat ID
                $stmt = $db->prepare("SELECT $sessionIdColumn FROM chat_sessions WHERE $chatIdColumn = ? ORDER BY last_message_time DESC LIMIT 1");
                $stmt->execute([$chatId]);
                $sessionId = $stmt->fetchColumn();
                
                if ($sessionId) {
                    logWebhook("Found session ID associated with chat ID: $sessionId");
                    
                    // Make sure this session is marked as active
                    $stmt = $db->prepare("UPDATE chat_sessions SET status = 'active' WHERE $sessionIdColumn = ?");
                    $stmt->execute([$sessionId]);
                } else {
                    // If no session is found for this chat ID, get the most recent session overall
                    $stmt = $db->prepare("SELECT $sessionIdColumn FROM chat_sessions ORDER BY last_message_time DESC LIMIT 1");
                    $stmt->execute();
                    $sessionId = $stmt->fetchColumn();
                    
                    if ($sessionId) {
                        logWebhook("Using most recent session from database (inactive): $sessionId");
                        
                        // Update this session to be active and associate with this chat ID
                        $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ?, status = 'active' WHERE $sessionIdColumn = ?");
                        $stmt->execute([$chatId, $sessionId]);
                    } else {
                        // If still no session, create a new one
                        $sessionId = 'agent_session_' . substr(md5(uniqid()), 0, 10);
                        logWebhook("Created new agent session ID: $sessionId", 'INFO');
                        
                        // Try to create this session in the database
                        $stmt = $db->prepare("INSERT INTO chat_sessions ($sessionIdColumn, $chatIdColumn, status, created_at, updated_at, last_message_time) 
                                            VALUES (?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                        $stmt->execute([$sessionId, $chatId]);
                        logWebhook("Created new chat session in database");
                    }
                }
            }
        } catch (Exception $e) {
            logWebhook("Error handling session: " . $e->getMessage(), 'WARN');
            
            // As a last resort, create a new session
            $sessionId = 'fallback_session_' . substr(md5(uniqid()), 0, 10);
            logWebhook("Created fallback session ID: $sessionId", 'INFO');
        }
    }
    
    // Store the message in the database
    $messageTableName = 'chat_messages';
    
    // Get the correct column names
    $messageSessionIdColumn = getDynamicColumnName($db, $messageTableName, ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // Check if we have the appropriate columns
    $senderTypeColumn = getDynamicColumnName($db, $messageTableName, ['sender_type', 'type']);
    $messageColumn = getDynamicColumnName($db, $messageTableName, ['message', 'content', 'text']);
    $senderIdColumn = getDynamicColumnName($db, $messageTableName, ['sender_id', 'creator_id']);
    
    // Double-check the session exists and is active
    $stmt = $db->prepare("SELECT id, status, last_message_time FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $sessionData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sessionData) {
        logWebhook("CRITICAL ERROR: Session $sessionId not found in database despite earlier checks!", 'ERROR');
        // Create the session as a last resort
        $stmt = $db->prepare("INSERT INTO chat_sessions ($sessionIdColumn, $chatIdColumn, status, created_at, updated_at, last_message_time) 
                              VALUES (?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$sessionId, $chatId]);
        logWebhook("Created missing session $sessionId as last resort");
    } else if ($sessionData['status'] !== 'active') {
        logWebhook("Session $sessionId has status '{$sessionData['status']}', updating to 'active'");
        $stmt = $db->prepare("UPDATE chat_sessions SET status = 'active' WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
    }
    
    // Check if this is a duplicate message we've already saved
    $stmt = $db->prepare("SELECT id FROM $messageTableName 
                         WHERE $messageSessionIdColumn = ? 
                         AND $messageColumn = ? 
                         AND $senderTypeColumn = 'agent' 
                         AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $stmt->execute([$sessionId, $messageText]);
    $existingMessage = $stmt->fetchColumn();
    
    if ($existingMessage) {
        logWebhook("Duplicate message detected, skipping save (existing ID: $existingMessage)");
    } else {
        // Insert the message
        $query = "INSERT INTO $messageTableName 
                ($messageSessionIdColumn, $senderTypeColumn, $messageColumn, $senderIdColumn, created_at) 
                VALUES (?, 'agent', ?, ?, ?)";
        
        try {
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $sessionId,
                $messageText,
                $creatorId,
                $timestamp
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                logWebhook("Database error on message insert: " . json_encode($errorInfo), 'ERROR');
                throw new Exception("Failed to insert message into database: " . $errorInfo[2]);
            }
            
            $messageId = $db->lastInsertId();
            logWebhook("SUCCESS: Saved agent message to database: $messageId for session $sessionId");
            
            // Update the session's last activity time
            $stmt = $db->prepare("UPDATE chat_sessions SET last_message_time = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE $sessionIdColumn = ?");
            $stmt->execute([$sessionId]);
            
            // Verify the message was actually saved
            $stmt = $db->prepare("SELECT id, $messageColumn FROM $messageTableName WHERE id = ?");
            $stmt->execute([$messageId]);
            $savedMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($savedMessage) {
                logWebhook("Verification: Message $messageId was saved successfully. Content: " . substr($savedMessage[$messageColumn], 0, 50) . "...");
            } else {
                logWebhook("WARNING: Message $messageId does not appear in database after saving!", 'ERROR');
            }
        } catch (Exception $insertEx) {
            logWebhook("Exception saving message: " . $insertEx->getMessage(), 'ERROR');
            throw $insertEx;
        }
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Message processed successfully',
        'message_id' => $messageId,
        'session_id' => $sessionId
    ]);
    
} catch (Exception $e) {
    // Log error
    logWebhook("Error processing webhook: " . $e->getMessage(), 'ERROR');
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
