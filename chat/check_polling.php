<?php
/**
 * Chat Message Polling Check Tool
 * 
 * This script directly queries messages from the database for a session
 * to verify they are retrievable through the API.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Polling Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .visitor { background-color: #f8f9fa; }
        .agent { background-color: #e3f2fd; }
        .system { background-color: #fff3cd; }
        pre { margin: 0; white-space: pre-wrap; }
        .code-block { font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 4px; white-space: pre; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <h1>Chat Polling Check Tool</h1>
        <p class="lead">This tool verifies what messages would be returned by the polling API</p>
        
        <?php
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id'])) {
            $sessionId = $_POST['session_id'];
            $lastMessageId = $_POST['last_message_id'] ?? 0;
            
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-primary text-white">Polling Results for Session: ' . htmlspecialchars($sessionId) . '</div>';
            echo '<div class="card-body">';
            
            try {
                // Get database connection
                $db = getDb();
                
                // Get dynamic column names
                $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
                
                // First check if session exists
                $stmt = $db->prepare("SELECT status FROM chat_sessions WHERE $sessionIdColumn = ?");
                $stmt->execute([$sessionId]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$session) {
                    echo '<div class="alert alert-danger">Chat session not found!</div>';
                } else {
                    // Show session status
                    echo '<p>Session Status: <strong>' . $session['status'] . '</strong></p>';
                    
                    // Build query based on the filter provided
                    $params = [$sessionId];
                    $where = '';
                    
                    if (!empty($lastMessageId) && $lastMessageId > 0) {
                        $where = " AND id > ?";
                        $params[] = $lastMessageId;
                    }
                    
                    // Get messages using the EXACT same query as the API
                    $messagesTable = 'chat_messages';
                    $query = "SELECT * FROM $messagesTable 
                            WHERE $sessionIdColumn = ?$where 
                            ORDER BY created_at ASC
                            LIMIT 100";
                    
                    echo '<p>Query used: <code>' . htmlspecialchars($query) . '</code></p>';
                    echo '<p>Parameters: <code>' . htmlspecialchars(json_encode($params)) . '</code></p>';
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<p>Found ' . count($messages) . ' messages</p>';
                    
                    if (count($messages) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered">';
                        echo '<thead><tr>';
                        echo '<th>ID</th>';
                        echo '<th>Type</th>';
                        echo '<th>Message</th>';
                        echo '<th>Created At</th>';
                        echo '</tr></thead>';
                        echo '<tbody>';
                        
                        foreach ($messages as $message) {
                            $rowClass = '';
                            if ($message['sender_type'] === 'visitor') {
                                $rowClass = 'visitor';
                            } else if ($message['sender_type'] === 'agent') {
                                $rowClass = 'agent';
                            } else if ($message['sender_type'] === 'system') {
                                $rowClass = 'system';
                            }
                            
                            echo '<tr class="'.$rowClass.'">';
                            echo '<td>'.$message['id'].'</td>';
                            echo '<td>'.$message['sender_type'].'</td>';
                            echo '<td>'.htmlspecialchars($message['message']).'</td>';
                            echo '<td>'.$message['created_at'].'</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                        echo '</div>';
                        
                        echo '<h5 class="mt-4">JSON Response (Same as API would return)</h5>';
                        
                        // Format messages for the client (same as API)
                        $formattedMessages = [];
                        foreach ($messages as $message) {
                            $formattedMessages[] = [
                                'id' => $message['id'],
                                'sender_type' => $message['sender_type'],
                                'message' => $message['message'],
                                'timestamp' => $message['created_at'],
                                'sender_id' => $message['sender_id'] ?? null,
                                'metadata' => isset($message['metadata']) ? json_decode($message['metadata'], true) : null
                            ];
                        }
                        
                        $response = [
                            'status' => 'success',
                            'session_id' => $sessionId,
                            'session_status' => [
                                'status' => $session['status'],
                                'closed' => ($session['status'] === 'closed'),
                                'active' => ($session['status'] === 'active')
                            ],
                            'messages' => $formattedMessages,
                            'count' => count($formattedMessages),
                            'timestamp' => date('Y-m-d H:i:s'),
                            'has_new_messages' => count($formattedMessages) > 0
                        ];
                        
                        echo '<pre class="code-block">' . json_encode($response, JSON_PRETTY_PRINT) . '</pre>';
                    } else {
                        echo '<div class="alert alert-warning">No new messages found for this session.</div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div class="card">
            <div class="card-header">Check Messages for Session</div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session ID</label>
                        <input type="text" class="form-control" id="session_id" name="session_id" 
                               value="<?php echo isset($_POST['session_id']) ? htmlspecialchars($_POST['session_id']) : 'session_kwy37rqj2e'; ?>" required>
                        <div class="form-text">Enter the session ID to check (e.g., "session_kwy37rqj2e")</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="last_message_id" class="form-label">Last Message ID (optional)</label>
                        <input type="number" class="form-control" id="last_message_id" name="last_message_id" 
                               value="<?php echo isset($_POST['last_message_id']) ? htmlspecialchars($_POST['last_message_id']) : '0'; ?>">
                        <div class="form-text">If provided, only messages after this ID will be returned</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Check Messages</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">Common Issues</div>
            <div class="card-body">
                <h5>Agent replies not showing in chat interface</h5>
                <ol>
                    <li><strong>Database structure mismatch</strong> - Ensure you have both message_id and ring_central_message_id columns</li>
                    <li><strong>Session ID mismatch</strong> - RingCentral might be sending messages to a different session</li>
                    <li><strong>JavaScript polling issue</strong> - Client-side code might not be polling for new messages</li>
                </ol>
                
                <h5>Solutions</h5>
                <ul>
                    <li>Run <a href="fix_messages_table.php" class="link-primary">fix_messages_table.php</a> to ensure database structure is correct</li>
                    <li>Use <a href="debug_messages.php" class="link-primary">debug_messages.php</a> to check if messages are being stored</li>
                    <li>Add <code>?debug=true</code> to your chat interface URL to enable console logging</li>
                </ul>
                
                <div class="alert alert-info mt-3">
                    <strong>Tip:</strong> Make sure your JavaScript is correctly polling for messages with the right session ID.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
