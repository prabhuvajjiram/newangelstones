<?php
/**
 * Database Fix Script
 * 
 * This script adds the missing message_id column to the chat_messages table
 * to resolve the issue with RingCentral messages not being stored correctly.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/helpers.php';

// Set content type to HTML for browser viewing
header('Content-Type: text/html; charset=utf-8');
echo '<h1>Database Fix Tool</h1>';

function executeQuery($db, $query, $description) {
    echo "<h3>$description</h3>";
    echo "<pre>$query</pre>";
    
    try {
        $result = $db->exec($query);
        echo '<p style="color: green; font-weight: bold;">✓ Query executed successfully!</p>';
        return true;
    } catch (PDOException $e) {
        echo '<p style="color: red; font-weight: bold;">✗ Error: ' . $e->getMessage() . '</p>';
        return false;
    }
}

try {
    // Get database connection
    $db = getDb();
    
    // Check if message_id column exists
    $columns = getTableColumns($db, 'chat_messages');
    $hasMessageId = in_array('message_id', $columns);
    $hasRingCentralMessageId = in_array('ring_central_message_id', $columns);
    
    echo '<h2>Current Database Structure</h2>';
    echo '<p>Checking chat_messages table columns:</p>';
    echo '<ul>';
    foreach ($columns as $column) {
        echo '<li>' . $column . '</li>';
    }
    echo '</ul>';
    
    echo '<h2>Database Fixes</h2>';
    
    // Add message_id column if it doesn't exist
    if (!$hasMessageId) {
        $addColumnQuery = "ALTER TABLE chat_messages ADD COLUMN message_id VARCHAR(64) NULL AFTER sender_id";
        executeQuery($db, $addColumnQuery, "Adding message_id column");
    } else {
        echo '<p>message_id column already exists.</p>';
    }
    
    // Create index on message_id if needed
    if (!$hasMessageId) {
        $addIndexQuery = "ALTER TABLE chat_messages ADD INDEX (message_id)";
        executeQuery($db, $addIndexQuery, "Adding index on message_id");
    }
    
    // Create a view to make all code work regardless of column names
    $createViewQuery = "CREATE OR REPLACE VIEW chat_messages_compat AS 
        SELECT 
            id,
            session_id,
            message,
            sender_type,
            sender_id,
            COALESCE(message_id, ring_central_message_id) AS message_id,
            ring_central_message_id,
            status,
            created_at,
            metadata
        FROM chat_messages";
    
    executeQuery($db, $createViewQuery, "Creating compatibility view");
    
    // Check if fixes were applied correctly
    $columns = getTableColumns($db, 'chat_messages');
    $hasMessageId = in_array('message_id', $columns);
    
    echo '<h2>Database Structure After Fixes</h2>';
    echo '<p>Checking chat_messages table columns:</p>';
    echo '<ul>';
    foreach ($columns as $column) {
        echo '<li>' . $column . '</li>';
    }
    echo '</ul>';
    
    if ($hasMessageId) {
        echo '<div style="padding: 15px; background-color: #dff0d8; border-radius: 5px; margin-top: 20px;">
            <h3 style="color: #3c763d;">✓ Database structure fixed successfully!</h3>
            <p>The chat_messages table now has the necessary structure to work with both message_id and ring_central_message_id.</p>
            <p>Messages from RingCentral should now be stored correctly in the database.</p>
        </div>';
    } else {
        echo '<div style="padding: 15px; background-color: #f2dede; border-radius: 5px; margin-top: 20px;">
            <h3 style="color: #a94442;">✗ Some fixes could not be applied</h3>
            <p>Please check the error messages above and fix them manually.</p>
        </div>';
    }
    
    echo '<p><a href="test_chat.html">Back to Test Chat</a></p>';
    echo '<p><a href="debug_polling.php?session_id=' . ($_GET['session_id'] ?? '') . '">Debug Tool</a></p>';
    
} catch (Exception $e) {
    echo '<div style="color: red; font-weight: bold;">ERROR: ' . $e->getMessage() . '</div>';
}
?>
