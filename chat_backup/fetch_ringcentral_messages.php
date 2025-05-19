<?php
/**
 * Direct RingCentral Message Fetcher
 * 
 * This script directly polls the RingCentral API for messages and
 * stores them in the database, bypassing the need for webhooks.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set content type to HTML for browser viewing
header('Content-Type: text/html; charset=utf-8');
echo '<h1>RingCentral Message Fetcher</h1>';

// Get parameters
$sessionId = $_GET['session_id'] ?? '';
$chatId = $_GET['chat_id'] ?? RINGCENTRAL_TEAM_CHAT_ID;
$limit = $_GET['limit'] ?? 20;

if (empty($sessionId)) {
    echo '<div style="color: red; font-weight: bold;">ERROR: Missing session_id parameter</div>';
    echo '<p>Use ?session_id=YOUR_SESSION_ID in the URL</p>';
    exit;
}

try {
    // Get database connection
    $db = getDb();
    
    // Get dynamic column names
    $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
    
    // Check if session exists
    $stmt = $db->prepare("SELECT id, status FROM chat_sessions WHERE $sessionIdColumn = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo '<div style="color: red; font-weight: bold;">ERROR: Session not found in database</div>';
        exit;
    }
    
    echo '<p>Session ID: ' . htmlspecialchars($sessionId) . '</p>';
    echo '<p>Chat ID: ' . htmlspecialchars($chatId) . '</p>';
    
    // Initialize RingCentral client
    $client = new RingCentralTeamMessagingClient();
    
    if (!$client->isAuthenticated()) {
        echo '<div style="color: red; font-weight: bold;">ERROR: RingCentral client not authenticated</div>';
        exit;
    }
    
    echo '<h2>Fetching Messages from RingCentral</h2>';
    
    // Fetch messages from RingCentral
    try {
        $messages = $client->getMessages($chatId, $limit);
        echo '<p>Found ' . count($messages) . ' messages in RingCentral</p>';
        
        // Display messages
        echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
            <tr style="background-color: #f0f0f0;">
                <th>ID</th>
                <th>Creator ID</th>
                <th>Text</th>
                <th>Creation Time</th>
                <th>Action</th>
            </tr>';
        
        $newMessages = 0;
        $existingMessages = 0;
        
        foreach ($messages as $message) {
            $messageId = $message['id'] ?? '';
            $messageText = $message['text'] ?? '';
            $creatorId = $message['creatorId'] ?? '';
            $creationTime = $message['creationTime'] ?? '';
            
            // Skip system messages
            if (strpos($messageText, SYSTEM_MESSAGE_FLAG) !== false) {
                echo '<tr style="background-color: #ffffd0;">
                    <td>' . htmlspecialchars($messageId) . '</td>
                    <td>' . htmlspecialchars($creatorId) . '</td>
                    <td>' . htmlspecialchars($messageText) . ' <i>(System message - skipped)</i></td>
                    <td>' . htmlspecialchars($creationTime) . '</td>
                    <td>Skipped</td>
                </tr>';
                continue;
            }
            
            // Check if message already exists in database
            $stmt = $db->prepare("SELECT id FROM chat_messages WHERE ring_central_message_id = ? OR message_id = ?");
            $stmt->execute([$messageId, $messageId]);
            $existingMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingMessage) {
                echo '<tr style="background-color: #e0f0e0;">
                    <td>' . htmlspecialchars($messageId) . '</td>
                    <td>' . htmlspecialchars($creatorId) . '</td>
                    <td>' . htmlspecialchars($messageText) . ' <i>(Already in database)</i></td>
                    <td>' . htmlspecialchars($creationTime) . '</td>
                    <td>Exists</td>
                </tr>';
                $existingMessages++;
            } else {
                // Insert new message into database
                $senderType = 'agent'; // Assume all messages from RingCentral are from agents
                
                // Store message
                $insertStmt = $db->prepare("INSERT INTO chat_messages 
                    ($sessionIdColumn, sender_type, message, ring_central_message_id, message_id, sender_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $created = date('Y-m-d H:i:s', strtotime($creationTime));
                $insertStmt->execute([$sessionId, $senderType, $messageText, $messageId, $messageId, $creatorId, $created]);
                
                echo '<tr style="background-color: #d0f0d0;">
                    <td>' . htmlspecialchars($messageId) . '</td>
                    <td>' . htmlspecialchars($creatorId) . '</td>
                    <td>' . htmlspecialchars($messageText) . ' <i>(Added to database)</i></td>
                    <td>' . htmlspecialchars($creationTime) . '</td>
                    <td>Added</td>
                </tr>';
                $newMessages++;
            }
        }
        
        echo '</table>';
        
        echo '<div style="margin-top: 20px; padding: 10px; background-color: ' . ($newMessages > 0 ? '#d0f0d0' : '#f0f0f0') . '; border-radius: 5px;">
            <p><strong>Summary:</strong></p>
            <ul>
                <li>' . $newMessages . ' new messages added to database</li>
                <li>' . $existingMessages . ' existing messages skipped</li>
                <li>' . (count($messages) - $newMessages - $existingMessages) . ' system messages skipped</li>
            </ul>
        </div>';
        
    } catch (Exception $e) {
        echo '<div style="color: red; font-weight: bold;">ERROR: ' . $e->getMessage() . '</div>';
    }
    
    // Navigation
    echo '<h2>Actions</h2>';
    echo '<ul>';
    echo '<li><a href="?session_id=' . urlencode($sessionId) . '&limit=5">Fetch Last 5 Messages</a></li>';
    echo '<li><a href="?session_id=' . urlencode($sessionId) . '&limit=20">Fetch Last 20 Messages</a></li>';
    echo '<li><a href="?session_id=' . urlencode($sessionId) . '&limit=50">Fetch Last 50 Messages</a></li>';
    echo '</ul>';
    
    echo '<p><a href="test_chat.html">Back to Test Chat</a></p>';
    
} catch (Exception $e) {
    echo '<div style="color: red; font-weight: bold;">ERROR: ' . $e->getMessage() . '</div>';
}
?>
