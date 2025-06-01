<?php
/**
 * Chat System Troubleshooting Tool
 * 
 * A diagnostic script to check session status, message counts,
 * and verify that agent messages are properly stored and retrievable.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
if (!function_exists('getDynamicColumnName')) {
    require_once __DIR__ . '/api/helpers.php';
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Connect to database
try {
    $db = getDb();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to format data in a human-readable way
function formatData($data) {
    if (is_array($data)) {
        return '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
    } else {
        return htmlspecialchars($data);
    }
}

// Get the correct column names
$sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
$chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
$senderTypeColumn = getDynamicColumnName($db, 'chat_messages', ['sender_type', 'type']);
$messageColumn = getDynamicColumnName($db, 'chat_messages', ['message', 'content', 'text']);
$messageSessionIdColumn = getDynamicColumnName($db, 'chat_messages', ['session_id', 'sessionid', 'chat_session_id', 'session']);

// Get session ID from request
$sessionId = $_GET['session_id'] ?? null;

// Get action from request
$action = $_GET['action'] ?? 'status';

// Database schema check
$tables = [];
$stmt = $db->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// Check chat_sessions table schema
$sessionColumns = [];
if (in_array('chat_sessions', $tables)) {
    $stmt = $db->query("DESCRIBE chat_sessions");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sessionColumns[] = $row;
    }
}

// Check chat_messages table schema
$messageColumns = [];
if (in_array('chat_messages', $tables)) {
    $stmt = $db->query("DESCRIBE chat_messages");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messageColumns[] = $row;
    }
}

// Check active sessions
$activeSessions = [];
$stmt = $db->query("SELECT * FROM chat_sessions WHERE status = 'active' ORDER BY last_message_time DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $activeSessions[] = $row;
}

// Check recent agent messages
$recentAgentMessages = [];
$stmt = $db->prepare("SELECT * FROM chat_messages WHERE $senderTypeColumn = 'agent' ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recentAgentMessages[] = $row;
}

// Process specific actions
$actionResult = null;
if ($action === 'fix' && $sessionId) {
    // Get the chat ID from RingCentral config
    $chatId = defined('RINGCENTRAL_TEAM_CHAT_ID') ? RINGCENTRAL_TEAM_CHAT_ID : '147193044998';
    
    // Update this session to be associated with the default chat ID
    $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ?, status = 'active', last_message_time = CURRENT_TIMESTAMP WHERE $sessionIdColumn = ?");
    $result = $stmt->execute([$chatId, $sessionId]);
    
    if ($result) {
        $actionResult = "Updated session $sessionId to be associated with chat ID $chatId and marked as active";
    } else {
        $actionResult = "Failed to update session: " . json_encode($stmt->errorInfo());
    }
} else if ($action === 'inject' && $sessionId) {
    // Inject a test agent message into this session
    $testMessage = "This is a test agent message injected at " . date('Y-m-d H:i:s');
    
    $stmt = $db->prepare("INSERT INTO chat_messages ($messageSessionIdColumn, $senderTypeColumn, $messageColumn, created_at) VALUES (?, 'agent', ?, CURRENT_TIMESTAMP)");
    $result = $stmt->execute([$sessionId, $testMessage]);
    
    if ($result) {
        $messageId = $db->lastInsertId();
        $actionResult = "Injected test agent message (ID: $messageId) into session $sessionId";
        
        // Also update the session's last activity time
        $stmt = $db->prepare("UPDATE chat_sessions SET last_message_time = CURRENT_TIMESTAMP WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
    } else {
        $actionResult = "Failed to inject test message: " . json_encode($stmt->errorInfo());
    }
} else if ($action === 'check' && $sessionId) {
    // Check messages for this session
    $messages = [];
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE $messageSessionIdColumn = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$sessionId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = $row;
    }
    
    // Get session details
    $stmt = $db->prepare("SELECT * FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $actionResult = [
        'session' => $session,
        'messages' => $messages,
        'message_count' => count($messages),
        'agent_message_count' => count(array_filter($messages, function($m) use ($senderTypeColumn) { 
            return $m[$senderTypeColumn] === 'agent'; 
        }))
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Troubleshooting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .table-sm {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid my-4">
        <h1>Chat System Troubleshooting</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Chat Sessions Table</h5>
                    </div>
                    <div class="card-body">
                        <h6>Column Structure</h6>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessionColumns as $column): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($column['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <h6 class="mt-3">Session ID Column: <?php echo htmlspecialchars($sessionIdColumn); ?></h6>
                        <h6>Chat ID Column: <?php echo htmlspecialchars($chatIdColumn); ?></h6>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Chat Messages Table</h5>
                    </div>
                    <div class="card-body">
                        <h6>Column Structure</h6>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messageColumns as $column): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($column['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <h6 class="mt-3">Message Session ID Column: <?php echo htmlspecialchars($messageSessionIdColumn); ?></h6>
                        <h6>Sender Type Column: <?php echo htmlspecialchars($senderTypeColumn); ?></h6>
                        <h6>Message Content Column: <?php echo htmlspecialchars($messageColumn); ?></h6>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Active Sessions</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="mb-3">
                            <div class="input-group">
                                <select name="session_id" class="form-control">
                                    <option value="">Select a session</option>
                                    <?php foreach ($activeSessions as $session): ?>
                                    <option value="<?php echo htmlspecialchars($session[$sessionIdColumn]); ?>" <?php echo $sessionId === $session[$sessionIdColumn] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($session[$sessionIdColumn]); ?> (Last: <?php echo htmlspecialchars($session['last_message_time']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="action" class="form-control">
                                    <option value="check" <?php echo $action === 'check' ? 'selected' : ''; ?>>Check Messages</option>
                                    <option value="fix" <?php echo $action === 'fix' ? 'selected' : ''; ?>>Fix Chat ID</option>
                                    <option value="inject" <?php echo $action === 'inject' ? 'selected' : ''; ?>>Inject Test Message</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Go</button>
                            </div>
                        </form>
                        
                        <?php if (!empty($actionResult)): ?>
                        <div class="alert alert-info">
                            <?php if (is_array($actionResult)): ?>
                                <h6>Session Details</h6>
                                <?php echo formatData($actionResult['session']); ?>
                                
                                <h6>Message Count: <?php echo $actionResult['message_count']; ?> (Agent: <?php echo $actionResult['agent_message_count']; ?>)</h6>
                                
                                <h6>Messages</h6>
                                <?php foreach ($actionResult['messages'] as $message): ?>
                                <div class="card mb-2">
                                    <div class="card-header py-1 <?php echo $message[$senderTypeColumn] === 'agent' ? 'bg-success text-white' : 'bg-info'; ?>">
                                        <?php echo htmlspecialchars($message[$senderTypeColumn]); ?> - <?php echo htmlspecialchars($message['created_at']); ?> (ID: <?php echo $message['id']; ?>)
                                    </div>
                                    <div class="card-body py-2">
                                        <?php echo nl2br(htmlspecialchars($message[$messageColumn])); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($actionResult); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <h6 class="mt-3">All Active Sessions (<?php echo count($activeSessions); ?>)</h6>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Session ID</th>
                                    <th>Chat ID</th>
                                    <th>Last Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeSessions as $session): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($session[$sessionIdColumn]); ?></td>
                                    <td><?php echo htmlspecialchars($session[$chatIdColumn] ?? 'none'); ?></td>
                                    <td><?php echo htmlspecialchars($session['last_message_time']); ?></td>
                                    <td>
                                        <a href="?session_id=<?php echo urlencode($session[$sessionIdColumn]); ?>&action=check" class="btn btn-sm btn-info">Check</a>
                                        <a href="?session_id=<?php echo urlencode($session[$sessionIdColumn]); ?>&action=fix" class="btn btn-sm btn-warning">Fix</a>
                                        <a href="?session_id=<?php echo urlencode($session[$sessionIdColumn]); ?>&action=inject" class="btn btn-sm btn-primary">Test</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Recent Agent Messages</h5>
                    </div>
                    <div class="card-body">
                        <h6>Last 10 Agent Messages</h6>
                        <?php if (empty($recentAgentMessages)): ?>
                        <div class="alert alert-warning">No agent messages found</div>
                        <?php else: ?>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Session</th>
                                    <th>Time</th>
                                    <th>Content</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAgentMessages as $message): ?>
                                <tr>
                                    <td><?php echo $message['id']; ?></td>
                                    <td><?php echo htmlspecialchars($message[$messageSessionIdColumn]); ?></td>
                                    <td><?php echo htmlspecialchars($message['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($message[$messageColumn], 0, 50)) . (strlen($message[$messageColumn]) > 50 ? '...' : ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
