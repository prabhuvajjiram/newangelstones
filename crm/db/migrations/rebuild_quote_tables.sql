-- First drop tables in correct order to avoid foreign key constraints issues
DROP TABLE IF EXISTS `quote_status_history`;
DROP TABLE IF EXISTS `quote_items`;
DROP TABLE IF EXISTS `quotes`;

-- Create quotes table first as it's referenced by others
CREATE TABLE `quotes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quote_number` varchar(20) NOT NULL,
    `customer_id` int(11) DEFAULT NULL,
    `customer_email` varchar(100) DEFAULT NULL,
    `total_amount` decimal(10,2) NOT NULL,
    `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `status` varchar(50) NOT NULL DEFAULT 'pending',
    `valid_until` date DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `quote_number` (`quote_number`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `quotes_customer_fk` 
    FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create quote_items table
CREATE TABLE `quote_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quote_id` int(11) NOT NULL,
    `product_type` varchar(50) NOT NULL,
    `model` varchar(20) NOT NULL,
    `size` varchar(20) NOT NULL,
    `color_id` int(11) NOT NULL,
    `length` decimal(10,2) NOT NULL,
    `breadth` decimal(10,2) NOT NULL,
    `sqft` decimal(10,2) NOT NULL,
    `cubic_feet` decimal(10,2) NOT NULL,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) NOT NULL,
    `total_price` decimal(10,2) NOT NULL,
    `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `special_monument_id` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `quote_id` (`quote_id`),
    CONSTRAINT `quote_items_quote_fk` 
    FOREIGN KEY (`quote_id`) 
    REFERENCES `quotes` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create quote_status_history table
CREATE TABLE `quote_status_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quote_id` int(11) NOT NULL,
    `status` varchar(50) NOT NULL,
    `notes` text DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `quote_id` (`quote_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `quote_status_history_quote_fk` 
    FOREIGN KEY (`quote_id`) 
    REFERENCES `quotes` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
    CONSTRAINT `quote_status_history_user_fk` 
    FOREIGN KEY (`created_by`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
