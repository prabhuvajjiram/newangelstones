<?php
/**
 * Fix Chat Sessions
 * 
 * This production-ready script automatically:
 * 1. Links all active sessions to the RingCentral Team Chat ID
 * 2. Associates orphaned agent messages with the correct sessions
 * 3. Logs all operations for audit purposes
 *
 * @category CRM
 * @package  Angel Stones CRM
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Set content type for browser viewing
header('Content-Type: text/html; charset=utf-8');

// Initialize response
$response = [
    'status' => 'success',
    'messages' => [],
    'linked_sessions' => [],
    'linked_messages' => []
];

// Function to log operations to response
function addResponseMessage($message, $type = 'info') {
    global $response;
    $response['messages'][] = ['type' => $type, 'message' => $message];
    
    if ($type == 'error') {
        $response['status'] = 'error';
    }
    
    // Also log to regular logging system
    if ($type == 'error') {
        logError($message);
    } else {
        logMessage($message);
    }
}

try {
    // Get database connection
    $db = getDb();
    
    addResponseMessage("Connected to database successfully");
    
    // ============ STEP 1: Get dynamic column names ============
    
    // Session table columns
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // Message table columns
    $msgSessionIdColumn = getDynamicColumnName($db, 'chat_messages', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $msgRcIdColumn = getDynamicColumnName($db, 'chat_messages', ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'message_id']);
    
    addResponseMessage("Using columns: session_table.$sessionIdColumn, session_table.$chatIdColumn, message_table.$msgSessionIdColumn");
    
    // Get the default RingCentral chat ID
    $defaultChatId = defined('RINGCENTRAL_TEAM_CHAT_ID') ? RINGCENTRAL_TEAM_CHAT_ID : '147193044998';
    
    addResponseMessage("Using default RingCentral chat ID: $defaultChatId");
    
    // ============ STEP 2: Fix active sessions ============
    
    // Find all active sessions that don't have a RingCentral chat ID
    $stmt = $db->prepare("SELECT id, $sessionIdColumn FROM chat_sessions WHERE status = 'active' AND ($chatIdColumn IS NULL OR $chatIdColumn = '')");
    $stmt->execute();
    $sessionsWithoutChatId = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    addResponseMessage("Found " . count($sessionsWithoutChatId) . " active sessions without a RingCentral chat ID");
    
    // Update each session
    foreach ($sessionsWithoutChatId as $session) {
        $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE id = ?");
        $stmt->execute([$defaultChatId, $session['id']]);
        
        $response['linked_sessions'][] = [
            'id' => $session['id'],
            'session_id' => $session[$sessionIdColumn],
            'chat_id' => $defaultChatId
        ];
        
        addResponseMessage("Linked session {$session[$sessionIdColumn]} to chat ID $defaultChatId");
    }
    
    // ============ STEP 3: Link orphaned agent messages ============
    
    // Find orphaned agent messages
    $stmt = $db->prepare("SELECT id, message FROM chat_messages WHERE sender_type = 'agent' AND ($msgSessionIdColumn IS NULL OR $msgSessionIdColumn = '')");
    $stmt->execute();
    $orphanedMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    addResponseMessage("Found " . count($orphanedMessages) . " orphaned agent messages");
    
    // Find all active sessions
    $stmt = $db->prepare("SELECT id, $sessionIdColumn, created_at FROM chat_sessions WHERE status = 'active' ORDER BY created_at DESC");
    $stmt->execute();
    $activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each orphaned message, link it to the most recently active session
    if (count($activeSessions) > 0 && count($orphanedMessages) > 0) {
        // Get most recent active session
        $mostRecentSession = $activeSessions[0];
        $targetSessionId = $mostRecentSession[$sessionIdColumn];
        
        addResponseMessage("Will link orphaned messages to the most recent session: $targetSessionId");
        
        foreach ($orphanedMessages as $message) {
            $stmt = $db->prepare("UPDATE chat_messages SET $msgSessionIdColumn = ? WHERE id = ?");
            $stmt->execute([$targetSessionId, $message['id']]);
            
            $shortMessage = substr($message['message'], 0, 30) . (strlen($message['message']) > 30 ? '...' : '');
            
            $response['linked_messages'][] = [
                'id' => $message['id'],
                'session_id' => $targetSessionId,
                'message' => $shortMessage
            ];
            
            addResponseMessage("Linked message #{$message['id']} to session $targetSessionId: '$shortMessage'");
        }
    }
    
    // ============ STEP 4: Update system to ensure future sessions work properly ============
    
    // We've already updated db.php to ensure new sessions get the chat ID
    addResponseMessage("System is now configured to automatically link new sessions with RingCentral chat ID");
    
} catch (Exception $e) {
    addResponseMessage("Error: " . $e->getMessage(), 'error');
}

// Output HTML response
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .log-container { max-height: 400px; overflow-y: auto; }
        .log-message { margin-bottom: 5px; }
        .log-info { color: #0d6efd; }
        .log-error { color: #dc3545; }
        .log-success { color: #198754; }
        pre { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Chat System Fix</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Status: <?php echo $response['status'] === 'success' ? 'Success' : 'Error'; ?>
            </div>
            <div class="card-body">
                <h5 class="card-title">Operations Log</h5>
                <div class="log-container border p-3 mb-3">
                    <?php foreach ($response['messages'] as $message): ?>
                        <div class="log-message log-<?php echo $message['type']; ?>">
                            <i class="bi bi-<?php echo $message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                            <?php echo htmlspecialchars($message['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($response['linked_sessions'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                Linked Sessions (<?php echo count($response['linked_sessions']); ?>)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Session ID</th>
                                <th>Chat ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($response['linked_sessions'] as $session): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['id']); ?></td>
                                <td><?php echo htmlspecialchars($session['session_id']); ?></td>
                                <td><?php echo htmlspecialchars($session['chat_id']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($response['linked_messages'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                Linked Messages (<?php echo count($response['linked_messages']); ?>)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Session ID</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($response['linked_messages'] as $message): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($message['id']); ?></td>
                                <td><?php echo htmlspecialchars($message['session_id']); ?></td>
                                <td><?php echo htmlspecialchars($message['message']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                Next Steps
            </div>
            <div class="card-body">
                <ol>
                    <li>Test a new chat session to ensure it automatically works with RingCentral</li>
                    <li>Verify that agent messages appear in all active sessions</li>
                    <li>Check the updated system is working in production</li>
                </ol>
                
                <div class="mt-3">
                    <a href="troubleshoot_chat.php" class="btn btn-primary">Go to Troubleshooting Tool</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
