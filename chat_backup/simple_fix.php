<?php
/**
 * Simple Chat Session Fix
 * 
 * A minimal script to fix RingCentral chat associations
 */

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Content type
header('Content-Type: text/plain');

// Include essential files
try {
    // Define entry point for security
    if (!defined('LOCAL_ENTRY_POINT')) {
        define('LOCAL_ENTRY_POINT', true);
    }
    
    echo "Starting chat session fix...\n";
    
    // Try to include needed files
    echo "Loading dependencies...\n";
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        echo "✓ Loaded config.php\n";
    } else {
        echo "✘ Failed to load config.php\n";
    }
    
    if (file_exists(__DIR__ . '/db.php')) {
        require_once __DIR__ . '/db.php';
        echo "✓ Loaded db.php\n";
    } else {
        echo "✘ Failed to load db.php\n";
    }
    
    if (file_exists(__DIR__ . '/api/helpers.php')) {
        require_once __DIR__ . '/api/helpers.php';
        echo "✓ Loaded helpers.php\n";
    } else {
        echo "✘ Failed to load helpers.php\n";
        // Create simple placeholder for log function if not found
        if (!function_exists('logMessage')) {
            function logMessage($msg) {
                echo "LOG: $msg\n";
            }
        }
    }
    
    // Attempt to connect to database
    echo "Connecting to database...\n";
    $db = null;
    try {
        $db = getDb();
        echo "✓ Database connection successful\n";
    } catch (Exception $e) {
        echo "✘ Database connection failed: " . $e->getMessage() . "\n";
        echo "Using fallback database connection method...\n";
        
        // Try fallback connection method
        try {
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $db = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "✓ Fallback database connection successful\n";
            } else {
                echo "✘ DB constants not defined\n";
            }
        } catch (Exception $e2) {
            echo "✘ Fallback connection also failed: " . $e2->getMessage() . "\n";
        }
    }
    
    // Exit if no database connection
    if (!$db) {
        echo "No database connection available. Exiting.\n";
        exit;
    }
    
    // Step 1: Get RingCentral team chat ID
    echo "Getting RingCentral chat ID...\n";
    $defaultChatId = '147193044998'; // Default hardcoded value
    
    if (defined('RINGCENTRAL_TEAM_CHAT_ID')) {
        $defaultChatId = RINGCENTRAL_TEAM_CHAT_ID;
        echo "✓ Using configured chat ID: $defaultChatId\n";
    } else {
        echo "! Using default chat ID: $defaultChatId\n";
    }
    
    // Step 2: Simple fix for chat sessions
    echo "Fixing chat sessions...\n";
    
    try {
        // Get column names from tables
        $columnInfo = $db->query("SHOW COLUMNS FROM chat_sessions")->fetchAll(PDO::FETCH_COLUMN);
        
        $chatIdColumn = '';
        $sessionIdColumn = '';
        
        foreach (['ring_central_chat_id', 'ringcentral_chat_id', 'rc_chat_id', 'chat_id'] as $col) {
            if (in_array($col, $columnInfo)) {
                $chatIdColumn = $col;
                break;
            }
        }
        
        foreach (['session_id', 'sessionid', 'chat_session_id', 'session'] as $col) {
            if (in_array($col, $columnInfo)) {
                $sessionIdColumn = $col;
                break;
            }
        }
        
        echo "✓ Found columns: sessionID='$sessionIdColumn', chatID='$chatIdColumn'\n";
        
        // Update active sessions
        $query = "UPDATE chat_sessions SET $chatIdColumn = ? WHERE status = 'active' AND ($chatIdColumn IS NULL OR $chatIdColumn = '')";
        $stmt = $db->prepare($query);
        $updated = $stmt->execute([$defaultChatId]);
        $count = $stmt->rowCount();
        echo "✓ Updated $count active sessions with chat ID\n";
        
        // Step 3: Try to fix orphaned messages
        echo "Linking orphaned messages...\n";
        
        // Get message table columns
        $msgColumnInfo = $db->query("SHOW COLUMNS FROM chat_messages")->fetchAll(PDO::FETCH_COLUMN);
        
        $msgSessionIdColumn = '';
        
        foreach (['session_id', 'sessionid', 'chat_session_id', 'session'] as $col) {
            if (in_array($col, $msgColumnInfo)) {
                $msgSessionIdColumn = $col;
                break;
            }
        }
        
        echo "✓ Messages table session column: '$msgSessionIdColumn'\n";
        
        // Find the most recent active session
        $query = "SELECT $sessionIdColumn FROM chat_sessions WHERE status = 'active' ORDER BY created_at DESC LIMIT 1";
        $recentSession = $db->query($query)->fetchColumn();
        
        if ($recentSession) {
            echo "✓ Found recent session: $recentSession\n";
            
            // Link orphaned agent messages to this session
            $query = "UPDATE chat_messages SET $msgSessionIdColumn = ? 
                     WHERE sender_type = 'agent' AND ($msgSessionIdColumn IS NULL OR $msgSessionIdColumn = '')";
            $stmt = $db->prepare($query);
            $updated = $stmt->execute([$recentSession]);
            $count = $stmt->rowCount();
            echo "✓ Linked $count orphaned messages to session\n";
        } else {
            echo "! No active sessions found\n";
        }
        
        echo "Fix complete!\n";
    } catch (Exception $e) {
        echo "Error during fix: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "Critical error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
