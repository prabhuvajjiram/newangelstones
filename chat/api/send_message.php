<?php
/**
 * RingCentral Message Sending API
 * 
 * Sends messages via RingCentral API
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

// Define log file and logging function
$logFile = __DIR__ . '/../ringcentral_chat.log';

function logMessage($message, $level = 'INFO') {
    global $logFile;
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
    logMessage("Received POST data in send_message.php: " . json_encode($input));
    
    // Check required fields
    if (empty($input['session_id']) || empty($input['message'])) {
        throw new Exception("Missing required fields: session_id and message are required");
    }
    
    // Extract data
    $sessionId = $input['session_id'];
    $message = $input['message'];
    $visitorId = $input['visitor_id'] ?? null;
    $visitorName = $input['visitor_name'] ?? 'Anonymous';
    $visitorEmail = $input['visitor_email'] ?? null;
    $visitorPhone = $input['visitor_phone'] ?? null;
    
    // Get database connection
    $db = getDb();
    
    // Store visitor information and update session
    $visitorInfo = [
        'name' => $visitorName,
        'email' => $visitorEmail,
        'phone' => $visitorPhone
    ];
    
    // Update or create the chat session
    $sessionResult = createOrUpdateChatSession($db, $sessionId, $visitorInfo);
    logMessage("Session " . ($sessionResult == 'created' ? 'created' : 'updated') . ": $sessionId");
    
    // Store the message in the database
    $messageId = storeChatMessage($db, $sessionId, $message, 'visitor', $visitorId);
    
    if (!$messageId) {
        throw new Exception("Failed to store message in database");
    }
    
    logMessage("Saved message to database: $messageId");
    
    // Optional: Forward to RingCentral if FORWARD_TO_RINGCENTRAL is defined and true
    $forwardToRingCentral = defined('FORWARD_TO_RINGCENTRAL') && FORWARD_TO_RINGCENTRAL === true;
    
    if ($forwardToRingCentral) {
        try {
            // Initialize the RingCentral client
            require_once __DIR__ . '/../RingCentralTeamMessagingClient.php';
            
            // Determine which authentication method to use
            $authType = defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'oauth';
            $clientConfig = [
                'clientId' => RINGCENTRAL_CLIENT_ID,
                'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
                'serverUrl' => RINGCENTRAL_SERVER,
                'tokenPath' => __DIR__ . '/../secure_storage/rc_token.json',
                'teamChatId' => RINGCENTRAL_TEAM_CHAT_ID
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
            
            // Try to find a dedicated chat for this session
            $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
            $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
            
            $stmt = $db->prepare("SELECT $chatIdColumn FROM chat_sessions WHERE $sessionIdColumn = ?");
            $stmt->execute([$sessionId]);
            $dedicatedChatId = $stmt->fetchColumn();
            
            // If no dedicated chat found, use the default team chat ID
            $chatId = $dedicatedChatId ?: RINGCENTRAL_TEAM_CHAT_ID;
            
            // Post the message to RingCentral
            // Format visitor info as a nice message
            $formattedMessage = "**New Message from Website Visitor**\n\n";
            $formattedMessage .= "**Message:** {$message}\n\n";
            $formattedMessage .= "**Session ID:** {$sessionId}\n";
            
            if (!empty($visitorInfo['name'])) {
                $formattedMessage .= "**Name:** {$visitorInfo['name']}\n";
            }
            
            if (!empty($visitorInfo['email'])) {
                $formattedMessage .= "**Email:** {$visitorInfo['email']}\n";
            }
            
            if (!empty($visitorInfo['phone'])) {
                $formattedMessage .= "**Phone:** {$visitorInfo['phone']}\n";
            }
            
            $formattedMessage .= "\n*Sent via Angel Stones Chat Widget at " . date('Y-m-d H:i:s') . "*";
            
            // Use the standard postMessage method instead of postCustomerMessage
            $result = $rcClient->postMessage(
                $chatId,
                $formattedMessage
            );
            
            if ($result && isset($result['id'])) {
                // If successful, update the message record with RingCentral message ID
                $rcMsgIdColumn = getDynamicColumnName($db, 'chat_messages', ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'message_id']);
                $stmt = $db->prepare("UPDATE chat_messages SET $rcMsgIdColumn = ? WHERE id = ?");
                $stmt->execute([$result['id'], $messageId]);
                
                logMessage("Message forwarded to RingCentral successfully. RingCentral Message ID: " . $result['id']);
            } else {
                logMessage("Failed to forward message to RingCentral", 'WARN');
            }
        } catch (Exception $ringEx) {
            // Non-fatal exception - log it but don't fail the entire request
            logMessage("RingCentral forwarding exception: " . $ringEx->getMessage(), 'WARN');
        }
    } else {
        logMessage("Skipping RingCentral forwarding - feature disabled or not configured");
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully',
        'message_id' => $messageId,
        'session_id' => $sessionId
    ]);
    
} catch (Exception $e) {
    // Log error
    logError("Error in send_message.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}
?>
