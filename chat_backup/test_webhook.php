<?php
/**
 * Test Webhook Simulator for RingCentral
 * 
 * This script allows you to simulate incoming webhook events from RingCentral
 * to test the webhook handling functionality of the chat system.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Set content type to JSON for API-like responses
header('Content-Type: application/json');

// Get session ID from the query parameter
$sessionId = $_GET['session_id'] ?? null;
$testType = $_GET['type'] ?? 'message'; // message, close, etc.
$chatId = $_GET['chat_id'] ?? RINGCENTRAL_TEAM_CHAT_ID;
$message = $_GET['message'] ?? 'This is a test agent response from RingCentral';

// Connect to database
try {
    $db = getDb();
} catch (Exception $e) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}

// Check if session exists
if ($sessionId) {
    // Make sure helpers.php is included
    if (!function_exists('getDynamicColumnName')) {
        require_once __DIR__ . '/api/helpers.php';
    }
    
    // Use dynamic column detection
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    
    // Check if session exists
    $stmt = $db->prepare("SELECT id FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        die(json_encode([
            'status' => 'error',
            'message' => "Session $sessionId not found in database"
        ]));
    }
    
    // Update chat ID if needed
    $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE $sessionIdColumn = ?");
    $stmt->execute([$chatId, $sessionId]);
}

// Simulate RingCentral webhook event

// Generate a unique message ID
$simulatedMessageId = 'sim_' . uniqid();

// Store the simulated message in the database
if ($testType === 'message' && $sessionId) {
    if (!function_exists('storeMessageDynamic')) {
        require_once __DIR__ . '/api/helpers.php';
    }
    
    try {
        // Store the message
        $messageId = storeMessageDynamic($db, $sessionId, $message, 'agent', 'simulator', $simulatedMessageId);
        
        $response = [
            'status' => 'success',
            'message' => 'Simulated agent message stored in database',
            'data' => [
                'session_id' => $sessionId,
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'simulated_message_id' => $simulatedMessageId,
                'content' => $message
            ]
        ];
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => 'Failed to store message: ' . $e->getMessage()
        ];
    }
} else {
    // For other test types or if session ID is not provided
    $response = [
        'status' => 'error',
        'message' => 'Invalid test type or missing session ID'
    ];
}

// Return response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
