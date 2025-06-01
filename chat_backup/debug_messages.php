<?php
/**
 * Debug Messages Tool
 * 
 * This script checks for recent messages in the database and displays them
 * for troubleshooting message reception and display issues.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Messages Debug</title>
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
        <h1>Chat Messages Debug Tool</h1>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Messages for Last 24 Hours</span>
                        <a href="?refresh=true" class="btn btn-sm btn-primary">Refresh</a>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $db = getDb();
                            
                            // Get all messages from the last 24 hours
                            $stmt = $db->query("SELECT * FROM chat_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 100");
                            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($messages) === 0) {
                                echo '<div class="alert alert-warning">No messages found in the last 24 hours.</div>';
                            } else {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-bordered table-striped">';
                                echo '<thead><tr>';
                                echo '<th>ID</th>';
                                echo '<th>Session ID</th>';
                                echo '<th>Type</th>';
                                echo '<th>Message</th>';
                                echo '<th>Sender ID</th>';
                                echo '<th>RingCentral Msg ID</th>';
                                echo '<th>Status</th>';
                                echo '<th>Created At</th>';
                                echo '</tr></thead>';
                                echo '<tbody>';
                                
                                foreach ($messages as $message) {
                                    // Find the session_id column
                                    $sessionId = null;
                                    foreach ($message as $key => $value) {
                                        if (stripos($key, 'session') !== false && $key !== 'sender_id') {
                                            $sessionId = $value;
                                            break;
                                        }
                                    }
                                    
                                    // Find the RingCentral message ID column
                                    $rcMessageId = null;
                                    foreach ($message as $key => $value) {
                                        if (stripos($key, 'message_id') !== false || stripos($key, 'ring_central_message_id') !== false) {
                                            $rcMessageId = $value;
                                            break;
                                        }
                                    }
                                    
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
                                    echo '<td>'.$sessionId.'</td>';
                                    echo '<td>'.$message['sender_type'].'</td>';
                                    echo '<td>'.htmlspecialchars($message['message']).'</td>';
                                    echo '<td>'.$message['sender_id'].'</td>';
                                    echo '<td>'.$rcMessageId.'</td>';
                                    echo '<td>'.$message['status'].'</td>';
                                    echo '<td>'.$message['created_at'].'</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody></table>';
                                echo '</div>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Database Table Structure</div>
                    <div class="card-body">
                        <?php
                        try {
                            // Show chat_messages table structure
                            $stmt = $db->query("DESCRIBE chat_messages");
                            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo '<h5>chat_messages Table Structure</h5>';
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-bordered table-sm">';
                            echo '<thead><tr>';
                            echo '<th>Field</th>';
                            echo '<th>Type</th>';
                            echo '<th>Null</th>';
                            echo '<th>Key</th>';
                            echo '<th>Default</th>';
                            echo '<th>Extra</th>';
                            echo '</tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($columns as $column) {
                                echo '<tr>';
                                echo '<td>'.$column['Field'].'</td>';
                                echo '<td>'.$column['Type'].'</td>';
                                echo '<td>'.$column['Null'].'</td>';
                                echo '<td>'.$column['Key'].'</td>';
                                echo '<td>'.$column['Default'].'</td>';
                                echo '<td>'.$column['Extra'].'</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            echo '</div>';
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Recent Sessions</div>
                    <div class="card-body">
                        <?php
                        try {
                            // Show recent sessions
                            $stmt = $db->query("SELECT * FROM chat_sessions ORDER BY created_at DESC LIMIT 10");
                            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($sessions) === 0) {
                                echo '<div class="alert alert-warning">No sessions found.</div>';
                            } else {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-bordered table-sm">';
                                echo '<thead><tr>';
                                foreach (array_keys($sessions[0]) as $key) {
                                    echo '<th>'.$key.'</th>';
                                }
                                echo '</tr></thead>';
                                echo '<tbody>';
                                
                                foreach ($sessions as $session) {
                                    echo '<tr>';
                                    foreach ($session as $value) {
                                        echo '<td>'.htmlspecialchars($value).'</td>';
                                    }
                                    echo '</tr>';
                                }
                                
                                echo '</tbody></table>';
                                echo '</div>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Webhook Log (Last 20 Entries)</div>
                    <div class="card-body">
                        <?php
                        $logFile = __DIR__ . '/webhook.log';
                        if (file_exists($logFile)) {
                            $log = file_get_contents($logFile);
                            $lines = array_slice(array_filter(explode("\n", $log)), -20);
                            echo '<div class="code-block">';
                            foreach ($lines as $line) {
                                echo htmlspecialchars($line) . "\n";
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">No webhook log file found.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Common Issues</h3>
                <div class="alert alert-info">
                    <h5>Agent replies not showing in chat interface</h5>
                    <ul>
                        <li><strong>Check the webhook log</strong> to ensure incoming messages are being received</li>
                        <li><strong>Verify message columns</strong> - message_id and ring_central_message_id should both exist</li>
                        <li><strong>Check polling script</strong> - Ensure it's fetching all messages, not just visitor messages</li>
                        <li><strong>Run the fix_messages_table.php script</strong> if the database structure needs updating</li>
                    </ul>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="check_polling.php" class="btn btn-primary">Check Polling</a>
                    <a href="fix_messages_table.php" class="btn btn-warning">Fix Messages Table</a>
                    <a href="fetch_ringcentral_messages.php" class="btn btn-success">Fetch New Messages</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
