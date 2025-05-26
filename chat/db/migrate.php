<?php
/**
 * Database Migration Script
 * 
 * Creates the necessary tables for the chat system if they don't exist
 */

require_once __DIR__ . '/../config.php';

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Create chat_sessions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_sessions` (
        `id` varchar(50) NOT NULL,
        `visitor_name` varchar(100) DEFAULT NULL,
        `visitor_email` varchar(255) DEFAULT NULL,
        `visitor_phone` varchar(50) DEFAULT NULL,
        `team_chat_id` varchar(100) DEFAULT NULL,
        `status` enum('active','closed') NOT NULL DEFAULT 'active',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `status` (`status`),
        KEY `team_chat_id` (`team_chat_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create chat_messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_messages` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `session_id` varchar(50) NOT NULL,
        `team_chat_id` varchar(100) DEFAULT NULL,
        `message` text NOT NULL,
        `sender_type` enum('visitor','agent','system') NOT NULL,
        `visitor_name` varchar(100) DEFAULT NULL,
        `visitor_email` varchar(255) DEFAULT NULL,
        `visitor_phone` varchar(50) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `session_id` (`session_id`),
        KEY `team_chat_id` (`team_chat_id`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create chat_attachments table (for future use)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_attachments` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `message_id` bigint(20) UNSIGNED DEFAULT NULL,
        `file_name` varchar(255) NOT NULL,
        `file_path` varchar(512) NOT NULL,
        `file_type` varchar(100) NOT NULL,
        `file_size` int(11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `message_id` (`message_id`),
        CONSTRAINT `chat_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Database migration completed successfully!\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
