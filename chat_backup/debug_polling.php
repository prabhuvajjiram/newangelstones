<?php
/**
 * Debug Polling System
 * 
 * This script helps debug issues with the RingCentral message polling system
 * by directly interacting with the RingCentral API and your local database.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set content type to HTML for browser viewing
header('Content-Type: text/html; charset=utf-8');

// Helper function to format arrays/objects for display
function formatArray($array) {
    return '<pre>' . print_r($array, true) . '</pre>';
}

// Get session ID from URL parameter
$sessionId = $_GET['session_id'] ?? '';
$chatId = $_GET['chat_id'] ?? RINGCENTRAL_TEAM_CHAT_ID;
$action = $_GET['action'] ?? 'check';

// Initialize client
$client = new RingCentralTeamMessagingClient();

echo '<h1>RingCentral Polling Debug Tool</h1>';

if (empty($sessionId)) {
    echo '<div style="color: red; font-weight: bold;">ERROR: Missing session_id parameter</div>';
    echo '<h2>Usage</h2>';
    echo '<p>Add <code>?session_id=YOUR_SESSION_ID</code> to the URL.</p>';
    echo '<p>Optional parameters:</p>';
    echo '<ul>';
    echo '<li><code>action=check</code> - Check status (default)</li>';
    echo '<li><code>action=fetch</code> - Fetch messages from RingCentral</li>';
    echo '<li><code>action=sync</code> - Sync database with RingCentral</li>';
    echo '<li><code>action=test</code> - Send a test message</li>';
    echo '</ul>';
    exit;
}

echo '<h2>Session Info</h2>';
echo '<p>Session ID: ' . htmlspecialchars($sessionId) . '</p>';

try {
    // Get database connection
    $db = getDb();
    
    // Get dynamic column names
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
    
    // Check if session exists
    $stmt = $db->prepare("SELECT id, $chatIdColumn as chat_id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo '<div style="color: red; font-weight: bold;">ERROR: Session not found in database</div>';
        exit;
    }
    
    echo '<p>Database Record:</p>';
    echo formatArray($session);
    
    // Get messages from database
    $messagesTable = 'chat_messages';
    $query = "SELECT * FROM $messagesTable WHERE $sessionIdColumn = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute([$sessionId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<h2>Database Messages (' . count($messages) . ')</h2>';
    
    if (count($messages) > 0) {
        echo '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
              <tr style="background-color: #f0f0f0;">
                <th>ID</th>
                <th>Type</th>
                <th>Message</th>
                <th>Created At</th>
              </tr>';
        
        foreach ($messages as $message) {
            echo '<tr>
                  <td>' . $message['id'] . '</td>
                  <td>' . $message['sender_type'] . '</td>
                  <td>' . htmlspecialchars($message['message']) . '</td>
                  <td>' . $message['created_at'] . '</td>
                </tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p>No messages found in database for this session.</p>';
    }
    
    // Check RingCentral connection status
    echo '<h2>RingCentral Status</h2>';
    
    if ($client->isAuthenticated()) {
        echo '<p style="color: green;">✓ RingCentral client authenticated using JWT</p>';
        
        // Get chat ID being used
        $actualChatId = $session['chat_id'] ?? $chatId;
        echo '<p>Using chat ID: ' . $actualChatId . '</p>';
        
        // Perform action based on parameter
        switch ($action) {
            case 'fetch':
                echo '<h3>Fetching Messages from RingCentral</h3>';
                try {
                    // Get the last 10 messages from the chat
                    $chatMessages = $client->getMessages($actualChatId, 10);
                    echo '<p>RingCentral returned ' . count($chatMessages) . ' messages:</p>';
                    echo formatArray($chatMessages);
                    
                    // Check which messages exist in our database
                    echo '<h4>Matching Messages in Database:</h4>';
                    $foundCount = 0;
                    
                    foreach ($chatMessages as $rcMessage) {
                        $messageId = $rcMessage['id'] ?? '';
                        $messageText = $rcMessage['text'] ?? '';
                        
                        // Check if this message exists in our database
                        $stmt = $db->prepare("SELECT id FROM $messagesTable WHERE message_id = ? OR message LIKE ?");
                        $stmt->execute([$messageId, '%' . substr($messageText, 0, 50) . '%']);
                        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($exists) {
                            echo '<p style="color: green;">✓ Message found in database: ' . htmlspecialchars(substr($messageText, 0, 50)) . '...</p>';
                            $foundCount++;
                        } else {
                            echo '<p style="color: red;">✗ Message NOT found in database: ' . htmlspecialchars(substr($messageText, 0, 50)) . '...</p>';
                        }
                    }
                    
                    echo '<p>Found ' . $foundCount . ' of ' . count($chatMessages) . ' messages in the database.</p>';
                    
                } catch (Exception $e) {
                    echo '<div style="color: red; font-weight: bold;">ERROR fetching messages: ' . $e->getMessage() . '</div>';
                }
                break;
                
            case 'test':
                echo '<h3>Sending Test Message</h3>';
                try {
                    $testMessage = "Test message from debug tool at " . date('Y-m-d H:i:s');
                    $result = $client->postMessage($actualChatId, $testMessage);
                    
                    echo '<p>Message sent! Response:</p>';
                    echo formatArray($result);
                    
                    // Add the message to our database too
                    $stmt = $db->prepare("INSERT INTO $messagesTable 
                                         ($sessionIdColumn, sender_type, message, message_id, created_at) 
                                         VALUES (?, 'system', ?, ?, NOW())");
                    $messageId = $result['id'] ?? uniqid('local_');
                    $stmt->execute([$sessionId, $testMessage, $messageId]);
                    
                    echo '<p style="color: green;">✓ Test message also added to local database</p>';
                    
                } catch (Exception $e) {
                    echo '<div style="color: red; font-weight: bold;">ERROR sending test message: ' . $e->getMessage() . '</div>';
                }
                break;
                
            case 'sync':
                echo '<h3>Syncing Messages from RingCentral</h3>';
                try {
                    // Get messages from RingCentral
                    $chatMessages = $client->getMessages($actualChatId, 20);
                    echo '<p>RingCentral returned ' . count($chatMessages) . ' messages.</p>';
                    
                    // Process each message
                    $syncedCount = 0;
                    
                    foreach ($chatMessages as $rcMessage) {
                        $messageId = $rcMessage['id'] ?? '';
                        $messageText = $rcMessage['text'] ?? '';
                        $creatorId = $rcMessage['creatorId'] ?? '';
                        $creationTime = $rcMessage['creationTime'] ?? date('Y-m-d H:i:s');
                        
                        // Skip system messages
                        if (strpos($messageText, SYSTEM_MESSAGE_FLAG) !== false) {
                            echo '<p>Skipping system message: ' . htmlspecialchars(substr($messageText, 0, 30)) . '...</p>';
                            continue;
                        }
                        
                        // Check if this message exists in our database
                        $stmt = $db->prepare("SELECT id FROM $messagesTable WHERE message_id = ?");
                        $stmt->execute([$messageId]);
                        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$exists) {
                            // Add to database
                            $stmt = $db->prepare("INSERT INTO $messagesTable 
                                                 ($sessionIdColumn, sender_type, message, message_id, sender_id, created_at) 
                                                 VALUES (?, ?, ?, ?, ?, ?)");
                            
                            // Determine if this is from an agent or visitor
                            $senderType = ($creatorId === RINGCENTRAL_USER_ID) ? 'visitor' : 'agent';
                            
                            $stmt->execute([
                                $sessionId, 
                                $senderType,
                                $messageText,
                                $messageId,
                                $creatorId,
                                $creationTime
                            ]);
                            
                            echo '<p style="color: green;">✓ Added message to database: ' . htmlspecialchars(substr($messageText, 0, 50)) . '...</p>';
                            $syncedCount++;
                        } else {
                            echo '<p>Message already exists in database: ' . htmlspecialchars(substr($messageText, 0, 30)) . '...</p>';
                        }
                    }
                    
                    echo '<p>Synced ' . $syncedCount . ' new messages to the database.</p>';
                    
                } catch (Exception $e) {
                    echo '<div style="color: red; font-weight: bold;">ERROR syncing messages: ' . $e->getMessage() . '</div>';
                }
                break;
                
            default:
                echo '<p>No action performed. Choose from fetch, test, or sync.</p>';
        }
        
    } else {
        echo '<div style="color: red; font-weight: bold;">ERROR: RingCentral client is not authenticated</div>';
        echo '<p>JWT Token Status: ' . ($client->getJwtTokenStatus() ?? 'Unknown') . '</p>';
    }
    
} catch (Exception $e) {
    echo '<div style="color: red; font-weight: bold;">ERROR: ' . $e->getMessage() . '</div>';
}

// Navigation links
echo '<h2>Actions</h2>';
echo '<ul>';
echo '<li><a href="?session_id=' . urlencode($sessionId) . '&action=check">Check Status</a></li>';
echo '<li><a href="?session_id=' . urlencode($sessionId) . '&action=fetch">Fetch Messages from RingCentral</a></li>';
echo '<li><a href="?session_id=' . urlencode($sessionId) . '&action=test">Send Test Message</a></li>';
echo '<li><a href="?session_id=' . urlencode($sessionId) . '&action=sync">Sync Messages from RingCentral</a></li>';
echo '</ul>';

// Back link
echo '<p><a href="test_chat.html">Back to Test Chat</a></p>';
?>
