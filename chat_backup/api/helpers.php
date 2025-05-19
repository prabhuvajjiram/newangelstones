<?php
/**
 * Helper functions for dynamic database column handling
 */

// Define entry point constants for secure inclusion
if (!defined('LOCAL_ENTRY_POINT')) {
    die('Direct access not permitted');
}

/**
 * Get dynamic column name based on available columns in table
 * 
 * @param PDO $db Database connection
 * @param string $table Table name
 * @param array $possibleColumns List of possible column names to check
 * @param string $defaultColumn Default column name to return if nothing is found
 * @return string The actual column name found in the table
 */
function getDynamicColumnName($db, $table, $possibleColumns, $defaultColumn = '') {
    try {
        // Get table structure
        $tableInfo = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($tableInfo, 'Field');
        
        // Find matching column
        foreach ($possibleColumns as $colName) {
            if (in_array($colName, $columnNames)) {
                return $colName;
            }
        }
        
        // Return default if no match found
        return $defaultColumn ?: $possibleColumns[0];
    } catch (Exception $e) {
        logError("Error getting dynamic column name: " . $e->getMessage());
        return $defaultColumn ?: $possibleColumns[0];
    }
}

/**
 * Find session by chat ID using dynamic column detection
 * 
 * @param PDO $db Database connection
 * @param string $chatId RingCentral chat ID to lookup
 * @return string|false The session ID if found, false otherwise
 */
function findSessionByChatId($db, $chatId) {
    try {
        // Find the session associated with this chat using dynamic column names
        $sessionIdColumn = getDynamicColumnName($db, 'chat_sessions', ['session_id', 'sessionid', 'chat_session_id', 'session']);
        
        // Find RingCentral chat ID column
        $chatIdColumn = getDynamicColumnName($db, 'chat_sessions', ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id']);
        
        // Log what we're doing
        logMessage("Looking for session with chat ID $chatId using columns $sessionIdColumn and $chatIdColumn");
        
        $stmt = $db->prepare("SELECT $sessionIdColumn FROM chat_sessions WHERE $chatIdColumn = ?");
        $stmt->execute([$chatId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage('Error finding session by chat ID: ' . $e->getMessage(), 'ERROR');
        } else if (function_exists('logError')) {
            logError('Error finding session by chat ID: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Store chat message with dynamic column detection
 * 
 * @param PDO $db Database connection
 * @param string $sessionId Session ID for the message
 * @param string $message Message content
 * @param string $senderType Type of sender (visitor, agent, system)
 * @param string $senderId ID of the sender (optional)
 * @param string $ringCentralMessageId RingCentral message ID (optional)
 * @return int|false The message ID if stored successfully, false otherwise
 */
function storeMessageDynamic($db, $sessionId, $message, $senderType, $senderId = null, $ringCentralMessageId = null) {
    try {
        // Find the session_id column in messages table
        $sessionIdColumn = getDynamicColumnName($db, 'chat_messages', ['session_id', 'sessionid', 'chat_session_id', 'session']);
        $rcMsgIdColumn = getDynamicColumnName($db, 'chat_messages', ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'message_id']);
        
        // Insert message with dynamic column names
        $stmt = $db->prepare("INSERT INTO chat_messages 
            ($sessionIdColumn, message, sender_type, sender_id, $rcMsgIdColumn, status)
            VALUES 
            (?, ?, ?, ?, ?, 'sent')");
        
        $stmt->execute([$sessionId, $message, $senderType, $senderId, $ringCentralMessageId]);
        return $db->lastInsertId();
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage('Error storing chat message: ' . $e->getMessage(), 'ERROR');
        } else if (function_exists('logError')) {
            logError('Error storing chat message: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Get all column names from a database table
 * 
 * @param PDO $db Database connection
 * @param string $table Table name
 * @return array List of column names in the table
 */
function getTableColumns($db, $table) {
    try {
        // Get table structure
        $tableInfo = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        return array_column($tableInfo, 'Field');
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage('Error getting table columns: ' . $e->getMessage(), 'ERROR');
        } else if (function_exists('logError')) {
            logError('Error getting table columns: ' . $e->getMessage());
        }
        return [];
    }
}
?>
