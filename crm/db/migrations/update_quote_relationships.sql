-- First, drop existing foreign keys from quote_items
ALTER TABLE `quote_items`
DROP FOREIGN KEY IF EXISTS `quote_items_ibfk_2`,
DROP FOREIGN KEY IF EXISTS `quote_items_special_monument_fk`;

-- Remove the indexes for the dropped foreign keys
ALTER TABLE `quote_items`
DROP INDEX IF EXISTS `color_id`,
DROP INDEX IF EXISTS `quote_items_special_monument_fk`;

-- Create quote_history table
CREATE TABLE IF NOT EXISTS `quote_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quote_id` int(11) NOT NULL,
    `status` varchar(50) NOT NULL,
    `notes` text DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `quote_id` (`quote_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `quote_history_quote_fk` 
    FOREIGN KEY (`quote_id`) 
    REFERENCES `quotes` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
    CONSTRAINT `quote_history_user_fk` 
    FOREIGN KEY (`created_by`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
