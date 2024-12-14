-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
    `order_id` INT PRIMARY KEY AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `company_id` INT,
    `quote_id` INT,  -- Reference to the quote if order was created from a quote
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `order_date` DATETIME NOT NULL,
    `due_date` DATE,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `shipping_address` TEXT,
    `billing_address` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (quote_id) REFERENCES quotes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Items table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `item_type` ENUM('raw_material', 'finished_product', 'base_product', 'marker_product', 'sertop_product', 'slant_product') NOT NULL,
    `product_id` INT(11) NOT NULL,
    `product_type` VARCHAR(50) DEFAULT NULL,
    `model` VARCHAR(20) DEFAULT NULL,
    `size` VARCHAR(20) DEFAULT NULL,
    `color_id` INT(11) DEFAULT NULL,
    `length` DECIMAL(10,2) NOT NULL,
    `breadth` DECIMAL(10,2) NOT NULL,
    `height` DECIMAL(10,2) DEFAULT NULL,
    `sqft` DECIMAL(10,2) DEFAULT NULL,
    `cubic_feet` DECIMAL(10,2) DEFAULT NULL,
    `quantity` INT(11) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'in_production', 'ready', 'shipped') NOT NULL DEFAULT 'pending',
    `warehouse_id` INT(11) DEFAULT NULL,
    `special_monument_id` INT(11) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `color_id` (`color_id`),
    KEY `warehouse_id` (`warehouse_id`),
    KEY `product_type_id` (`item_type`, `product_id`),
    CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `order_items_color_fk` FOREIGN KEY (`color_id`) REFERENCES `stone_color_rates` (`id`),
    CONSTRAINT `order_items_warehouse_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order Items Manufacturing Details
CREATE TABLE IF NOT EXISTS `order_items_manufacturing` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_item_id` INT(11) NOT NULL,
    `raw_material_id` INT(11) DEFAULT NULL,
    `process_status` ENUM('pending', 'cutting', 'polishing', 'engraving', 'completed') NOT NULL DEFAULT 'pending',
    `estimated_completion_date` DATE DEFAULT NULL,
    `actual_completion_date` DATE DEFAULT NULL,
    `assigned_to` INT(11) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_item_id` (`order_item_id`),
    KEY `raw_material_id` (`raw_material_id`),
    KEY `assigned_to` (`assigned_to`),
    CONSTRAINT `order_items_manufacturing_item_fk` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `order_items_manufacturing_material_fk` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`),
    CONSTRAINT `order_items_manufacturing_user_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order Status History table
CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `order_status_history_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `order_status_history_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order Payments table
CREATE TABLE IF NOT EXISTS `order_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `order_payments_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `order_payments_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
