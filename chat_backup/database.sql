-- Chat Session Tables for Angel Stones Chat Widget

-- First, drop tables in reverse order of dependencies
SET FOREIGN_KEY_CHECKS=0;

-- Drop tables if they exist
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_sessions`;
DROP TABLE IF EXISTS `chat_teams`;
DROP TABLE IF EXISTS `chat_settings`;

SET FOREIGN_KEY_CHECKS=1;

-- Chat Sessions table - stores information about each chat session
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

-- Chat Messages table - stores all messages exchanged in a chat session
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `message` text NOT NULL,
  `sender_type` enum('visitor','agent','system') NOT NULL,
  `sender_id` varchar(64) DEFAULT NULL COMMENT 'ID of the sender (agent ID for agents)',
  `message_id` varchar(64) DEFAULT NULL COMMENT 'ID of the message in RingCentral',
  `ring_central_message_id` varchar(64) DEFAULT NULL COMMENT 'ID of the message in RingCentral',
  `status` enum('sent','delivered','read','failed') NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` json DEFAULT NULL COMMENT 'Additional message metadata',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat Teams table - stores information about RingCentral teams
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

-- Chat Settings table - stores configuration settings for the chat system
CREATE TABLE `chat_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `chat_settings` (`setting_key`, `setting_value`, `description`) VALUES
('triage_team_id', '147193044998', 'Default RingCentral team ID for initial triage'),
('idle_timeout_minutes', '30', 'Minutes of inactivity before marking a chat as idle'),
('close_timeout_hours', '24', 'Hours before automatically closing an idle chat'),
('enable_webhooks', '1', 'Whether to enable RingCentral webhooks for receiving messages'),
('enable_polling', '1', 'Whether to enable message polling as fallback'),
('polling_interval', '10000', 'Milliseconds between polling requests'),
('auth_type', 'jwt', 'Authentication type (jwt or oauth)'),
('jwt_token_expiry', '2628000', 'JWT token expiry in seconds (default 30 days)'),
('jwt_enabled', '1', 'Whether JWT authentication is enabled');
