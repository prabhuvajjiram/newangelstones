<?php
/**
 * Database initialization script for RingCentral Chat
 * 
 * This script checks for the existence of required tables and creates them if they don't exist
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

echo "<h1>Angel Stones RingCentral Chat - Database Setup</h1>";
echo "<pre>";

try {
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "Connecting to database {$dsn}...\n";
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "Connected successfully!\n\n";
    
    // Get list of existing tables
    echo "Checking for existing tables...\n";
    $existingTables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
    
    // Define tables we need
    $requiredTables = ['chat_sessions', 'chat_messages', 'chat_teams', 'chat_settings'];
    
    // Track which tables we create
    $createdTables = [];
    
    // Check each required table
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "✓ Table '{$table}' already exists\n";
        } else {
            echo "✗ Table '{$table}' does not exist - creating...\n";
            
            // Create the table based on its definition
            switch ($table) {
                case 'chat_sessions':
                    $db->exec("
                        CREATE TABLE `chat_sessions` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `session_id` varchar(64) NOT NULL COMMENT 'Unique session ID for the chat',
                          `visitor_name` varchar(255) DEFAULT NULL,
                          `visitor_email` varchar(255) DEFAULT NULL,
                          `visitor_phone` varchar(32) DEFAULT NULL,
                          `ring_central_chat_id` varchar(64) DEFAULT NULL COMMENT 'ID of the RingCentral chat/conversation',
                          `status` enum('active','closed','idle') NOT NULL DEFAULT 'active',
                          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          `last_message_time` timestamp NULL DEFAULT NULL,
                          `closed_at` timestamp NULL DEFAULT NULL,
                          `client_ip` varchar(45) DEFAULT NULL,
                          `user_agent` text DEFAULT NULL,
                          `referrer` text DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `session_id` (`session_id`),
                          KEY `status` (`status`),
                          KEY `ring_central_chat_id` (`ring_central_chat_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ");
                    $createdTables[] = $table;
                    break;
                    
                case 'chat_messages':
                    $db->exec("
                        CREATE TABLE `chat_messages` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `session_id` varchar(64) NOT NULL,
                          `message` text NOT NULL,
                          `sender_type` enum('visitor','agent','system') NOT NULL,
                          `sender_id` varchar(64) DEFAULT NULL COMMENT 'ID of the sender (agent ID for agents)',
                          `ring_central_message_id` varchar(64) DEFAULT NULL COMMENT 'ID of the message in RingCentral',
                          `status` enum('sent','delivered','read','failed') NOT NULL DEFAULT 'sent',
                          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `metadata` json DEFAULT NULL COMMENT 'Additional message metadata',
                          PRIMARY KEY (`id`),
                          KEY `session_id` (`session_id`),
                          KEY `created_at` (`created_at`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ");
                    $createdTables[] = $table;
                    break;
                    
                case 'chat_teams':
                    $db->exec("
                        CREATE TABLE `chat_teams` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `ring_central_team_id` varchar(64) NOT NULL,
                          `name` varchar(255) NOT NULL,
                          `description` text,
                          `is_active` tinyint(1) NOT NULL DEFAULT 1,
                          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `ring_central_team_id` (`ring_central_team_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ");
                    $createdTables[] = $table;
                    break;
                    
                case 'chat_settings':
                    $db->exec("
                        CREATE TABLE `chat_settings` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `setting_key` varchar(64) NOT NULL,
                          `setting_value` text,
                          `description` varchar(255) DEFAULT NULL,
                          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `setting_key` (`setting_key`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ");
                    
                    // Insert default settings
                    $db->exec("
                        INSERT INTO `chat_settings` (`setting_key`, `setting_value`, `description`) VALUES
                        ('triage_team_id', '" . RINGCENTRAL_DEFAULT_CHAT_ID . "', 'Default RingCentral team ID for initial triage'),
                        ('idle_timeout_minutes', '30', 'Minutes of inactivity before marking a chat as idle'),
                        ('close_timeout_hours', '24', 'Hours before automatically closing an idle chat'),
                        ('enable_webhooks', '1', 'Whether to enable RingCentral webhooks for receiving messages'),
                        ('enable_polling', '1', 'Whether to enable message polling as fallback'),
                        ('polling_interval', '10000', 'Milliseconds between polling requests');
                    ");
                    
                    $createdTables[] = $table;
                    break;
            }
            
            echo "✓ Created table '{$table}'\n";
        }
    }
    
    // Summary
    echo "\nSummary:\n";
    echo "- Total required tables: " . count($requiredTables) . "\n";
    echo "- Tables already existing: " . (count($requiredTables) - count($createdTables)) . "\n";
    echo "- Tables created: " . count($createdTables) . "\n";
    
    if (count($createdTables) > 0) {
        echo "\nThe following tables were created:\n";
        foreach ($createdTables as $table) {
            echo "- {$table}\n";
        }
    }
    
    echo "\nDatabase setup completed successfully!";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "</pre>";
echo '<p><a href="test_chat.html" class="btn btn-primary">Go to Chat Test Page</a></p>';
?>
