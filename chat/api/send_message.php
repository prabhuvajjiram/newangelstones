<?php
/**
 * RingCentral Message Sending API
 * 
 * Sends messages via RingCentral API
 */

// Include configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Add missing logError function
function logError($message) {
    logMessage($message, 'ERROR');
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
    $input = json_decode(file_get_contents('php://input'), true);
    logMessage("Received POST data in send_message.php: " . json_encode($input));
    if (empty($input['session_id']) || empty($input['message'])) {
        throw new Exception("Missing required fields: session_id and message are required");
    }
    $sessionId = $input['session_id'];
    $message = $input['message'];
    $visitorId = $input['visitor_id'] ?? null;
    $visitorName = $input['visitor_name'] ?? 'Anonymous';
    $visitorEmail = $input['visitor_email'] ?? null;
    $visitorPhone = $input['visitor_phone'] ?? null;

    // Get database connection
    try {
        $db = getDb();
    } catch (Exception $dbEx) {
        logError("DB connection error: " . $dbEx->getMessage() . "\n" . $dbEx->getTraceAsString());
        throw $dbEx;
    }

    $visitorInfo = [
        'name' => $visitorName,
        'email' => $visitorEmail,
        'phone' => $visitorPhone
    ];
    try {
        $sessionResult = createOrUpdateChatSession($db, $sessionId, $visitorInfo);
        logMessage("Session " . ($sessionResult == 'created' ? 'created' : 'updated') . ": $sessionId");
    } catch (Exception $sessEx) {
        logError("Session create/update error: " . $sessEx->getMessage() . "\n" . $sessEx->getTraceAsString());
        throw $sessEx;
    }

    try {
        $messageId = storeChatMessage($db, $sessionId, $message, 'visitor', $visitorId);
    } catch (Exception $msgEx) {
        logError("Message store error: " . $msgEx->getMessage() . "\n" . $msgEx->getTraceAsString());
        throw $msgEx;
    }
    if (!$messageId) {
        throw new Exception("Failed to store message in database");
    }
    logMessage("Saved message to database: $messageId");

    $isNewSession = ($sessionResult == 'created');
    $ringcentralChatId = null;
    $forwardToRingCentral = defined('FORWARD_TO_RINGCENTRAL') && FORWARD_TO_RINGCENTRAL === true;

    if ($forwardToRingCentral) {
        try {
            require_once __DIR__ . '/../RingCentralTeamMessagingClient.php';
            $authType = defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'oauth';
            $clientConfig = [
                'clientId' => RINGCENTRAL_CLIENT_ID,
                'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
                'serverUrl' => RINGCENTRAL_SERVER,
                'tokenPath' => __DIR__ . '/../secure_storage/rc_token.json',
                'teamChatId' => RINGCENTRAL_TEAM_CHAT_ID
            ];
            if ($authType === 'jwt' && defined('RINGCENTRAL_JWT_TOKEN')) {
                $clientConfig['jwtToken'] = RINGCENTRAL_JWT_TOKEN;
                logMessage("Using JWT authentication for RingCentral");
            } else {
                if (defined('RINGCENTRAL_USERNAME') && defined('RINGCENTRAL_PASSWORD') && defined('RINGCENTRAL_EXTENSION')) {
                    $clientConfig['username'] = RINGCENTRAL_USERNAME;
                    $clientConfig['password'] = RINGCENTRAL_PASSWORD;
                    $clientConfig['extension'] = RINGCENTRAL_EXTENSION;
                    logMessage("Using OAuth authentication for RingCentral");
                }
            }
            $rcClient = new RingCentralTeamMessagingClient($clientConfig);

            $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
            $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
            if ($isNewSession) {
                $groupName = "Chat with $visitorName ($sessionId)";
                $groupMembers = ["purchase@theangelstones.com"];
                try {
                    $group = $rcClient->createTeam($groupName, '', $groupMembers);
                    logMessage("RingCentral createTeam response: " . json_encode($group));
                } catch (Exception $rcCreateEx) {
                    logError("RingCentral createTeam exception: " . $rcCreateEx->getMessage() . "\n" . $rcCreateEx->getTraceAsString());
                    throw $rcCreateEx;
                }
                if (isset($group['id'])) {
                    $ringcentralChatId = $group['id'];
                    try {
                        $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE $sessionIdColumn = ?");
                        $stmt->execute([$ringcentralChatId, $sessionId]);
                    } catch (Exception $dbUpdateEx) {
                        logError("DB update chatId error: " . $dbUpdateEx->getMessage() . "\n" . $dbUpdateEx->getTraceAsString());
                    }
                    logMessage("Created new RingCentral group: $ringcentralChatId for session $sessionId");
                } else {
                    logError("Failed to create RingCentral group for session $sessionId. Response: " . json_encode($group));
                }
            } else {
                try {
                    $stmt = $db->prepare("SELECT $chatIdColumn FROM chat_sessions WHERE $sessionIdColumn = ?");
                    $stmt->execute([$sessionId]);
                    $ringcentralChatId = $stmt->fetchColumn();
                } catch (Exception $dbSelectEx) {
                    logError("DB select chatId error: " . $dbSelectEx->getMessage() . "\n" . $dbSelectEx->getTraceAsString());
                }
            }

            $chatId = $ringcentralChatId ?: RINGCENTRAL_TEAM_CHAT_ID;

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

            try {
                $result = $rcClient->postMessage($chatId, $formattedMessage);
                logMessage("RingCentral postMessage response: " . json_encode($result));
            } catch (Exception $rcPostEx) {
                logError("RingCentral postMessage exception: " . $rcPostEx->getMessage() . "\n" . $rcPostEx->getTraceAsString());
                throw $rcPostEx;
            }
            if ($result && isset($result['id'])) {
                try {
                    $rcMsgIdColumn = getDynamicColumnName($db, 'chat_messages', ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'message_id']);
                    $stmt = $db->prepare("UPDATE chat_messages SET $rcMsgIdColumn = ? WHERE id = ?");
                    $stmt->execute([$result['id'], $messageId]);
                } catch (Exception $dbMsgUpdateEx) {
                    logError("DB update messageId error: " . $dbMsgUpdateEx->getMessage() . "\n" . $dbMsgUpdateEx->getTraceAsString());
                }
                logMessage("Message forwarded to RingCentral successfully. RingCentral Message ID: " . $result['id']);
            } else {
                logError("Failed to forward message to RingCentral. Response: " . json_encode($result));
            }
        } catch (Exception $ringEx) {
            logError("RingCentral forwarding exception: " . $ringEx->getMessage() . "\n" . $ringEx->getTraceAsString());
            throw $ringEx;
        }
    } else {
        logMessage("Skipping RingCentral forwarding - feature disabled or not configured");
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully',
        'message_id' => $messageId,
        'session_id' => $sessionId
    ]);
} catch (Exception $e) {
    logError("Error in send_message.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
    exit();
}
?>
