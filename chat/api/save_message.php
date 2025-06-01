<?php
/**
 * Save Message Endpoint
 * 
 * Handles saving chat messages to the database and forwarding to RingCentral team chat
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../team_chat.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['session_id'], $input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message_id' => null
];

try {
    // Get database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Prepare message data
    $sessionId = $input['session_id'];
    $message = $input['message'];
    $senderType = $input['sender_type'] ?? 'visitor';
    $visitorName = $input['visitor_name'] ?? '';
    $visitorEmail = $input['visitor_email'] ?? '';
    $visitorPhone = $input['visitor_phone'] ?? '';

    // Save to database
    $stmt = $pdo->prepare("INSERT INTO chat_messages 
        (session_id, message, sender_type, visitor_name, visitor_email, visitor_phone, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $sessionId,
        $message,
        $senderType,
        $visitorName,
        $visitorEmail,
        $visitorPhone
    ]);
    
    $messageId = $pdo->lastInsertId();
    
    // If this is a visitor message and team chat is enabled, forward to RingCentral
    if ($senderType === 'visitor' && FORWARD_TO_RINGCENTRAL && RINGCENTRAL_TEAM_CHAT_ENABLED) {
        $teamChatId = $input['team_chat_id'] ?? null;
        
        if ($teamChatId) {
            $teamChat = new TeamChatManager(RINGCENTRAL_JWT_TOKEN);
            $result = $teamChat->sendMessage(
                $teamChatId,
                $message,
                $visitorName ?: 'Visitor',
                false
            );
            
            if (!$result['success']) {
                error_log("Failed to send to team chat: " . ($result['error'] ?? 'Unknown error'));
            }
        }
    }
    
    $response['success'] = true;
    $response['message_id'] = $messageId;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $response['error'] = 'Database error';
    http_response_code(500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
