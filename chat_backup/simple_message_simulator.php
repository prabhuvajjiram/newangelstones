<?php
/**
 * Simple Message Simulator for Testing
 * 
 * This script simulates messages from an agent to test the chat interface
 * without requiring a working RingCentral connection.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Set content type to HTML for browser viewing
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Simulator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; }
        .message-preview { 
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Agent Message Simulator</h1>
        <p class="lead">Use this tool to simulate messages from an agent</p>
        
        <div class="card mb-4">
            <div class="card-header">
                Active Chat Sessions
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th>Messages</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $db = getDb();
                                $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
                                
                                // Get active sessions
                                $stmt = $db->query("SELECT id, $sessionIdColumn as session_id, status, created_at, updated_at, 
                                                  (SELECT COUNT(*) FROM chat_messages WHERE $sessionIdColumn = chat_sessions.$sessionIdColumn) as message_count 
                                                  FROM chat_sessions 
                                                  WHERE status != 'closed' 
                                                  ORDER BY updated_at DESC 
                                                  LIMIT 10");
                                
                                $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($sessions) === 0) {
                                    echo '<tr><td colspan="5" class="text-center">No active sessions found</td></tr>';
                                }
                                
                                foreach ($sessions as $session) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($session['session_id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($session['status']) . '</td>';
                                    echo '<td>' . htmlspecialchars($session['updated_at']) . '</td>';
                                    echo '<td>' . $session['message_count'] . '</td>';
                                    echo '<td>
                                            <a href="?session_id=' . urlencode($session['session_id']) . '" class="btn btn-sm btn-primary">Select</a>
                                          </td>';
                                    echo '</tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-danger">Error: ' . $e->getMessage() . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id']) && isset($_POST['message'])) {
            $sessionId = $_POST['session_id'];
            $message = $_POST['message'];
            
            try {
                $db = getDb();
                $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
                
                // Check if session exists
                $stmt = $db->prepare("SELECT id FROM chat_sessions WHERE $sessionIdColumn = ?");
                $stmt->execute([$sessionId]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$session) {
                    echo '<div class="alert alert-danger">Session not found</div>';
                } else {
                    // Generate a unique message ID
                    $messageId = 'sim_' . uniqid();
                    
                    // Insert message
                    $stmt = $db->prepare("INSERT INTO chat_messages 
                                        ($sessionIdColumn, sender_type, message, ring_central_message_id, message_id, sender_id, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    
                    $stmt->execute([
                        $sessionId,
                        'agent',
                        $message,
                        $messageId,
                        $messageId,
                        'simulator_agent'
                    ]);
                    
                    // Update session last activity
                    $stmt = $db->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE $sessionIdColumn = ?");
                    $stmt->execute([$sessionId]);
                    
                    echo '<div class="alert alert-success">Message sent successfully!</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
        
        // Form to send a message
        $sessionId = $_GET['session_id'] ?? '';
        
        if (!empty($sessionId)) {
            echo '<div class="card">';
            echo '<div class="card-header">Send Agent Message</div>';
            echo '<div class="card-body">';
            
            // Get recent messages
            try {
                $db = getDb();
                $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
                
                $stmt = $db->prepare("SELECT * FROM chat_messages WHERE $sessionIdColumn = ? ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$sessionId]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($messages) > 0) {
                    echo '<h5>Recent Messages</h5>';
                    echo '<div class="mb-3">';
                    
                    foreach (array_reverse($messages) as $msg) {
                        $type = $msg['sender_type'];
                        $bgColor = $type === 'visitor' ? 'bg-light' : ($type === 'agent' ? 'bg-info bg-opacity-10' : 'bg-secondary bg-opacity-10');
                        
                        echo '<div class="' . $bgColor . ' p-2 mb-2 rounded">';
                        echo '<strong>' . ucfirst($type) . ':</strong> ';
                        echo htmlspecialchars($msg['message']);
                        echo '<div class="text-muted small">' . $msg['created_at'] . '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                
                echo '<form method="post" action="">';
                echo '<input type="hidden" name="session_id" value="' . htmlspecialchars($sessionId) . '">';
                
                echo '<div class="form-group mb-3">';
                echo '<label for="message">Response from Agent:</label>';
                echo '<textarea class="form-control" id="message" name="message" rows="3" required></textarea>';
                echo '</div>';
                
                echo '<div class="message-preview d-none" id="preview-container">';
                echo '<h6>Message Preview:</h6>';
                echo '<div id="message-preview"></div>';
                echo '</div>';
                
                echo '<button type="submit" class="btn btn-primary">Send Response</button>';
                echo ' <a href="test_chat.html" class="btn btn-secondary">Back to Test Chat</a>';
                echo '</form>';
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>'; // card-body
            echo '</div>'; // card
        }
        ?>
    </div>
    
    <script>
        // Simple live preview
        const messageInput = document.getElementById('message');
        const previewContainer = document.getElementById('preview-container');
        const messagePreview = document.getElementById('message-preview');
        
        if (messageInput && previewContainer && messagePreview) {
            messageInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    previewContainer.classList.add('d-none');
                } else {
                    previewContainer.classList.remove('d-none');
                    messagePreview.textContent = this.value;
                }
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
