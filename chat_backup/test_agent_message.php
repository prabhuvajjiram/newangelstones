<?php
/**
 * Test Agent Message Display
 * 
 * This script manually inserts a test agent message and checks if it's 
 * retrievable through the compatibility view.
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
    <title>Test Agent Message</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Agent Message Test</h1>
        
        <?php
        $sessionId = $_POST['session_id'] ?? null;
        $message = $_POST['message'] ?? null;
        $action = $_POST['action'] ?? null;
        
        if ($action === 'insert' && $sessionId && $message) {
            try {
                $db = getDb();
                
                // Use the storeMessageDynamic function to insert the message
                $messageId = storeMessageDynamic(
                    $db, 
                    $sessionId, 
                    $message, 
                    'agent', 
                    'test_agent_' . time(), // Mock agent ID 
                    'test_rc_' . time() // Mock RingCentral message ID
                );
                
                if ($messageId) {
                    echo '<div class="alert alert-success">
                        <h4>Test message inserted successfully!</h4>
                        <p>Message ID: ' . $messageId . '</p>
                    </div>';
                    
                    // Now check if it can be seen via the compat view
                    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
                    
                    // Try using the compat view first
                    try {
                        $checkView = $db->query("SHOW TABLES LIKE 'chat_messages_compat'");
                        if ($checkView->rowCount() > 0) {
                            // View exists, use it
                            $stmt = $db->prepare("SELECT * FROM chat_messages_compat WHERE $sessionIdColumn = ? ORDER BY created_at DESC LIMIT 5");
                            $stmt->execute([$sessionId]);
                            $compatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo '<div class="card mb-4">
                                <div class="card-header">Messages from Compatibility View</div>
                                <div class="card-body">';
                            
                            if (count($compatMessages) > 0) {
                                echo '<table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Message</th>
                                            <th>Message ID</th>
                                            <th>RC Message ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                                
                                foreach ($compatMessages as $msg) {
                                    echo '<tr>
                                        <td>' . $msg['id'] . '</td>
                                        <td>' . $msg['sender_type'] . '</td>
                                        <td>' . htmlspecialchars($msg['message']) . '</td>
                                        <td>' . ($msg['message_id'] ?? 'NULL') . '</td>
                                        <td>' . ($msg['ring_central_message_id'] ?? 'NULL') . '</td>
                                    </tr>';
                                }
                                
                                echo '</tbody></table>';
                            } else {
                                echo '<div class="alert alert-warning">No messages found in compatibility view!</div>';
                            }
                            
                            echo '</div></div>';
                        } else {
                            echo '<div class="alert alert-warning">Compatibility view does not exist!</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error checking compatibility view: ' . $e->getMessage() . '</div>';
                    }
                    
                    // Check regular table too
                    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE $sessionIdColumn = ? ORDER BY created_at DESC LIMIT 5");
                    $stmt->execute([$sessionId]);
                    $directMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<div class="card mb-4">
                        <div class="card-header">Messages from Direct Table</div>
                        <div class="card-body">';
                    
                    if (count($directMessages) > 0) {
                        echo '<table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Message</th>';
                        
                        // Show all columns that might contain message IDs
                        foreach ($directMessages[0] as $colName => $value) {
                            if (stripos($colName, 'message_id') !== false || stripos($colName, 'ring_central') !== false) {
                                echo '<th>' . $colName . '</th>';
                            }
                        }
                        
                        echo '</tr>
                            </thead>
                            <tbody>';
                        
                        foreach ($directMessages as $msg) {
                            echo '<tr>
                                <td>' . $msg['id'] . '</td>
                                <td>' . $msg['sender_type'] . '</td>
                                <td>' . htmlspecialchars($msg['message']) . '</td>';
                            
                            // Show values for message ID columns
                            foreach ($msg as $colName => $value) {
                                if (stripos($colName, 'message_id') !== false || stripos($colName, 'ring_central') !== false) {
                                    echo '<td>' . ($value ?? 'NULL') . '</td>';
                                }
                            }
                            
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<div class="alert alert-warning">No messages found in direct table!</div>';
                    }
                    
                    echo '</div></div>';
                    
                    // Test polling API directly
                    echo '<div class="card mb-4">
                        <div class="card-header">Test Polling API</div>
                        <div class="card-body">
                            <p>Click the button below to test if the polling API would return this message:</p>
                            <a href="check_polling.php?session_id=' . urlencode($sessionId) . '" class="btn btn-primary" target="_blank">Test Polling API</a>
                        </div>
                    </div>';
                    
                } else {
                    echo '<div class="alert alert-danger">Failed to insert test message!</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">Insert Test Agent Message</div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="insert">
                    
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session ID</label>
                        <input type="text" class="form-control" id="session_id" name="session_id" 
                               value="<?php echo $sessionId ?? 'session_kwy37rqj2e'; ?>" required>
                        <div class="form-text">Enter the session ID to send a test message to</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Test Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required><?php echo $message ?? 'This is a test agent reply from the diagnostic tool at ' . date('H:i:s'); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Insert Test Message</button>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Fixing Agent Messages Display</h3>
            <ol>
                <li>Use this tool to insert a test agent message into a session</li>
                <li>Verify that the message appears in both the compatibility view and direct table</li>
                <li>Use the "Test Polling API" button to check if the message would be returned by the API</li>
                <li>If messages are in the database but not appearing in your chat interface, the issue is likely with:
                    <ul>
                        <li>The polling API not using the compatibility view (now fixed)</li>
                        <li>The frontend JavaScript not displaying agent messages</li>
                        <li>A session ID mismatch between what RingCentral sends and what your UI shows</li>
                    </ul>
                </li>
            </ol>
            <div class="mt-3">
                <a href="debug_messages.php" class="btn btn-secondary">Back to Debug Messages</a>
            </div>
        </div>
    </div>
</body>
</html>
