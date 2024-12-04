-- Add special_monument_id column to quote_items table
ALTER TABLE `quote_items`
ADD COLUMN IF NOT EXISTS `special_monument_id` int(11) DEFAULT NULL,
ADD CONSTRAINT `quote_items_special_monument_fk` 
FOREIGN KEY (`special_monument_id`) REFERENCES `special_monument` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
