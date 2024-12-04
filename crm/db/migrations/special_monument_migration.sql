-- Drop table if exists
DROP TABLE IF EXISTS `special_monument`;

-- Create special_monument table
CREATE TABLE `special_monument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sp_name` varchar(100) NOT NULL,
  `sp_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_name` (`sp_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert initial data
INSERT INTO `special_monument` (`id`, `sp_name`, `sp_value`, `created_at`, `updated_at`) VALUES
(1, 'Sigle Heart', '15.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(2, 'Double Heart', '20.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(3, 'Stacked Heart', '25.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(4, 'Book Top', '15.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(5, 'Champers', '5.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(6, 'Spl Monument Shape low', '30.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(7, 'Spl Monument Shape medium', '35.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(8, 'Spl Monument Shape High', '40.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04'),
(9, 'None', '0.00', '2024-12-01 20:52:04', '2024-12-01 20:52:04');

-- Now let's modify the quote_items table to add the special_monument_id column
ALTER TABLE `quote_items`
ADD COLUMN IF NOT EXISTS `special_monument_id` int(11) DEFAULT NULL;
