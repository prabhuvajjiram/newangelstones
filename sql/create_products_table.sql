CREATE TABLE IF NOT EXISTS `products` (
  `id` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `type` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `material` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_color` (`color`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample products
INSERT INTO `products` (`id`, `name`, `description`, `type`, `color`, `material`, `price`, `image`, `active`) VALUES
('bp001', 'Blue Pearl Granite', 'Elegant blue-grey granite with pearl-like luster', 'Granite', 'Blue', 'Natural Granite', 1299.00, 'bluepearl.jpg', 1),
('ib002', 'Indian Black Granite', 'Premium black granite with fine grain', 'Granite', 'Black', 'Natural Granite', 1499.00, 'indian-black.jpg', 1),
('vb003', 'Vizag Blue Granite', 'Distinctive blue granite with unique patterns', 'Granite', 'Blue', 'Natural Granite', 1599.00, 'vizag-blue.jpg', 1),
('pd004', 'Paradiso Granite', 'Luxurious brown granite with natural veining', 'Granite', 'Brown', 'Natural Granite', 1799.00, 'paradiso.jpg', 1),
('gl005', 'Galaxy Black Granite', 'Premium black granite with copper-colored flecks', 'Granite', 'Black', 'Natural Granite', 1899.00, 'galaxy.jpg', 1),
('wr006', 'White and Red Granite', 'Striking combination of white and red patterns', 'Granite', 'White', 'Natural Granite', 1699.00, 'white-and-red.jpg', 1);
