<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning and unserializing of the instance
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function getDb() {
    return Database::getInstance()->getConnection();
}

/**
 * Logs errors to the error log
 */
function logError($message, $data = []) {
    $log = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    if (!empty($data)) {
        $log .= "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    error_log($log, 3, __DIR__ . "/error.log");
}

/**
 * Creates or updates a chat session with dynamic column detection
 */
function createOrUpdateChatSession($db, $sessionId, $visitorInfo = []) {
    try {
        // Check table structure to get correct column names
        $tableInfo = $db->query("SHOW COLUMNS FROM chat_sessions")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($tableInfo, 'Field');
        
        // Find session_id column name
        $sessionIdColumn = 'session_id'; // Default
        $possibleSessionColumns = ['session_id', 'sessionid', 'chat_session_id', 'session'];
        
        $columnFound = false;
        foreach ($possibleSessionColumns as $colName) {
            if (in_array($colName, $columnNames)) {
                $sessionIdColumn = $colName;
                $columnFound = true;
                break;
            }
        }
        
        if (!$columnFound) {
            throw new Exception("Session ID column not found in chat_sessions table");
        }
        
        // Check if session exists
        $stmt = $db->prepare("SELECT id FROM chat_sessions WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
        $sessionExists = $stmt->fetchColumn();
        
        // Define the column names we need to work with
        $visitorNameCol = in_array('visitor_name', $columnNames) ? 'visitor_name' : 'name';
        $visitorEmailCol = in_array('visitor_email', $columnNames) ? 'visitor_email' : 'email';
        $visitorPhoneCol = in_array('visitor_phone', $columnNames) ? 'visitor_phone' : 'phone';
        $clientIpCol = in_array('client_ip', $columnNames) ? 'client_ip' : 'ip';
        $userAgentCol = in_array('user_agent', $columnNames) ? 'user_agent' : 'browser';
        $referrerCol = in_array('referrer', $columnNames) ? 'referrer' : 'source';
        
        // Find RingCentral chat ID column
        $ringCentralChatIdCol = null;
        $possibleRingCentralColumns = ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id'];
        
        foreach ($possibleRingCentralColumns as $colName) {
            if (in_array($colName, $columnNames)) {
                $ringCentralChatIdCol = $colName;
                break;
            }
        }
        
        // Get the default Ring Central chat ID if defined
        $defaultChatId = defined('RINGCENTRAL_TEAM_CHAT_ID') ? RINGCENTRAL_TEAM_CHAT_ID : 
                         (defined('RINGCENTRAL_DEFAULT_CHAT_ID') ? RINGCENTRAL_DEFAULT_CHAT_ID : '147193044998');
                         
        if ($sessionExists) {
            // Update existing session
            $updateQuery = "UPDATE chat_sessions SET 
                $visitorNameCol = COALESCE(:visitor_name, $visitorNameCol),
                $visitorEmailCol = COALESCE(:visitor_email, $visitorEmailCol),
                $visitorPhoneCol = COALESCE(:visitor_phone, $visitorPhoneCol),";
            
            // Add Ring Central chat ID column if it exists
            if ($ringCentralChatIdCol) {
                $updateQuery .= "
                $ringCentralChatIdCol = COALESCE($ringCentralChatIdCol, :ring_central_chat_id),";
            }
            
            $updateQuery .= "
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
                WHERE $sessionIdColumn = :session_id";
                
            $stmt = $db->prepare($updateQuery);
            
            // Bind Ring Central chat ID if column exists
            if ($ringCentralChatIdCol) {
                $stmt->bindValue(':ring_central_chat_id', $defaultChatId);
            }
        } else {
            // Create new session
            $insertQuery = "INSERT INTO chat_sessions 
                ($sessionIdColumn, $visitorNameCol, $visitorEmailCol, $visitorPhoneCol";
            
            // Add Ring Central chat ID column if it exists
            if ($ringCentralChatIdCol) {
                $insertQuery .= ", $ringCentralChatIdCol";
            }
            
            $insertQuery .= ", status, $clientIpCol, $userAgentCol, $referrerCol)
                VALUES 
                (:session_id, :visitor_name, :visitor_email, :visitor_phone";
            
            // Add Ring Central chat ID value if column exists
            if ($ringCentralChatIdCol) {
                $insertQuery .= ", :ring_central_chat_id";
            }
            
            $insertQuery .= ", 'active', :client_ip, :user_agent, :referrer)";
                
            $stmt = $db->prepare($insertQuery);
            
            // Bind Ring Central chat ID if column exists
            if ($ringCentralChatIdCol) {
                $stmt->bindValue(':ring_central_chat_id', $defaultChatId);
            }
            
            // Add client information
            $stmt->bindValue(':client_ip', getClientIP());
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
            $stmt->bindValue(':referrer', $_SERVER['HTTP_REFERER'] ?? null);
        }
        
        // Bind common parameters
        $stmt->bindValue(':session_id', $sessionId);
        $stmt->bindValue(':visitor_name', $visitorInfo['name'] ?? null);
        $stmt->bindValue(':visitor_email', $visitorInfo['email'] ?? null);
        $stmt->bindValue(':visitor_phone', $visitorInfo['phone'] ?? null);
        
        $stmt->execute();
        return $sessionExists ? 'updated' : 'created';
    } catch (PDOException $e) {
        logError("Error creating/updating chat session: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        logError("Error with chat session columns: " . $e->getMessage());
        return false;
    }
}

/**
 * Store a chat message in the database with dynamic column detection
 */
function storeChatMessage($db, $sessionId, $message, $senderType, $senderId = null, $ringCentralMessageId = null) {
    try {
        // Check messages table structure
        $msgTableInfo = $db->query("SHOW COLUMNS FROM chat_messages")->fetchAll(PDO::FETCH_ASSOC);
        $msgColumnNames = array_column($msgTableInfo, 'Field');
        
        // Find session_id column in messages table
        $msgSessionIdColumn = 'session_id'; // Default
        $possibleMsgColumns = ['session_id', 'sessionid', 'chat_session_id', 'session'];
        
        foreach ($possibleMsgColumns as $colName) {
            if (in_array($colName, $msgColumnNames)) {
                $msgSessionIdColumn = $colName;
                break;
            }
        }
        
        // Find ring_central_message_id column
        $rcMsgIdColumn = 'ring_central_message_id'; // Default
        $possibleRcMsgColumns = ['ring_central_message_id', 'ringcentral_message_id', 'rc_message_id', 'message_id'];
        
        foreach ($possibleRcMsgColumns as $colName) {
            if (in_array($colName, $msgColumnNames)) {
                $rcMsgIdColumn = $colName;
                break;
            }
        }
        
        // Use dynamic column names in query
        $stmt = $db->prepare("INSERT INTO chat_messages 
            ($msgSessionIdColumn, message, sender_type, sender_id, $rcMsgIdColumn, status)
            VALUES 
            (?, ?, ?, ?, ?, 'sent')");
            
        $stmt->execute([$sessionId, $message, $senderType, $senderId, $ringCentralMessageId]);
        $messageId = $db->lastInsertId();
        
        // Check sessions table structure
        $sessionTableInfo = $db->query("SHOW COLUMNS FROM chat_sessions")->fetchAll(PDO::FETCH_ASSOC);
        $sessionColumnNames = array_column($sessionTableInfo, 'Field');
        
        // Find session_id column in sessions table
        $sessionIdColumn = 'session_id'; // Default
        foreach ($possibleMsgColumns as $colName) {
            if (in_array($colName, $sessionColumnNames)) {
                $sessionIdColumn = $colName;
                break;
            }
        }
        
        // Update session's last message time with dynamic column name
        $stmt = $db->prepare("UPDATE chat_sessions SET last_message_time = CURRENT_TIMESTAMP WHERE $sessionIdColumn = ?");
        $stmt->execute([$sessionId]);
        
        return $messageId;
    } catch (PDOException $e) {
        logError("Error storing chat message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get chat messages for a session with dynamic column detection
 */
function getChatMessages($db, $sessionId, $limit = 50, $offset = 0) {
    try {
        // Check table structure to get correct column names
        $tableInfo = $db->query("SHOW COLUMNS FROM chat_messages")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($tableInfo, 'Field');
        
        // Find session_id column name
        $sessionIdColumn = 'session_id'; // Default
        $possibleColumns = ['session_id', 'sessionid', 'chat_session_id', 'session'];
        
        foreach ($possibleColumns as $colName) {
            if (in_array($colName, $columnNames)) {
                $sessionIdColumn = $colName;
                break;
            }
        }
        
        $stmt = $db->prepare("SELECT * FROM chat_messages 
            WHERE $sessionIdColumn = ? 
            ORDER BY created_at ASC 
            LIMIT ? OFFSET ?");
            
        $stmt->execute([$sessionId, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error retrieving chat messages: " . $e->getMessage());
        return [];
    }
}

/**
 * Link a session to a RingCentral chat with dynamic column detection
 */
function linkSessionToRingCentralChat($db, $sessionId, $ringCentralChatId) {
    try {
        // First check table structure to get correct column names
        $tableInfo = $db->query("SHOW COLUMNS FROM chat_sessions")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($tableInfo, 'Field');
        
        // Find session_id column name
        $sessionIdColumn = 'session_id'; // Default
        $possibleSessionColumns = ['session_id', 'sessionid', 'chat_session_id', 'session'];
        
        foreach ($possibleSessionColumns as $colName) {
            if (in_array($colName, $columnNames)) {
                $sessionIdColumn = $colName;
                break;
            }
        }
        
        // Find ring_central_chat_id column name
        $chatIdColumn = 'ring_central_chat_id'; // Default
        $possibleChatColumns = ['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id'];
        
        foreach ($possibleChatColumns as $colName) {
            if (in_array($colName, $columnNames)) {
                $chatIdColumn = $colName;
                break;
            }
        }
        
        // Use dynamic column names in query
        $stmt = $db->prepare("UPDATE chat_sessions SET $chatIdColumn = ? WHERE $sessionIdColumn = ?");
        $stmt->execute([$ringCentralChatId, $sessionId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        logError("Error linking session to RingCentral chat: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a dedicated RingCentral chat for a session
 */
function createDedicatedChat($db, $sessionId, $ringCentralChatId, $chatName) {
    try {
        // First store the team in chat_teams
        $stmt = $db->prepare("INSERT INTO chat_teams (ring_central_team_id, name, description) 
            VALUES (?, ?, ?)");
        $stmt->execute([$ringCentralChatId, $chatName, "Dedicated chat for session $sessionId"]);
        
        // Then link the session to this chat
        return linkSessionToRingCentralChat($db, $sessionId, $ringCentralChatId);
    } catch (PDOException $e) {
        logError("Error creating dedicated chat: " . $e->getMessage());
        return false;
    }
}

/**
 * Close a chat session
 */
function closeSession($db, $sessionId) {
    try {
        $stmt = $db->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = CURRENT_TIMESTAMP WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        logError("Error closing session: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a setting value from the database
 */
function getSetting($db, $key, $default = null) {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM chat_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        logError("Error getting setting: " . $e->getMessage());
        return $default;
    }
}

/**
 * Validates email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validates phone number (basic validation)
 */
function isValidPhone($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Check if phone number has between 10-15 digits
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

/**
 * Gets visitor's IP address
 */
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Generates a unique ID for visitors
 */
function generateVisitorId() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Gets or creates a visitor record
 */
function getOrCreateVisitor($db, $visitorId = null) {
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // If we have a cookie ID, try to find the visitor
    if ($visitorId) {
        $stmt = $db->prepare("SELECT * FROM visitors WHERE id = ?");
        $stmt->execute([$visitorId]);
        $visitor = $stmt->fetch();
        
        if ($visitor) {
            // Update last seen
            $updateStmt = $db->prepare("UPDATE visitors SET last_seen = CURRENT_TIMESTAMP, ip_address = ? WHERE id = ?");
            $updateStmt->execute([$ip, $visitorId]);
            return $visitor;
        }
    }
    
    // Create new visitor
    $visitorId = $visitorId ?: generateVisitorId();
    $cookieId = bin2hex(random_bytes(32));
    
    $stmt = $db->prepare("INSERT INTO visitors (id, ip_address, user_agent, cookie_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$visitorId, $ip, $userAgent, $cookieId]);
    
    setcookie(CHAT_COOKIE_NAME, $cookieId, time() + CHAT_COOKIE_EXPIRE, '/', '', true, true);
    
    return [
        'id' => $visitorId,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
        'cookie_id' => $cookieId,
        'first_seen' => date('Y-m-d H:i:s'),
        'last_seen' => date('Y-m-d H:i:s')
    ];
}
?>
