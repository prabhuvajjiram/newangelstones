USE angelstones_quotes_new;

-- Add quote_number column to quotes table
ALTER TABLE quotes
ADD COLUMN quote_number VARCHAR(50) NOT NULL UNIQUE AFTER id;

-- Drop old stone_colors table if it exists
DROP TABLE IF EXISTS stone_colors;

-- Create stone_color_rates table if it doesn't exist
CREATE TABLE IF NOT EXISTS stone_color_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    color_name VARCHAR(100) NOT NULL,
    price_increase_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
