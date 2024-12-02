-- Create stone_color_rates table if it doesn't exist
CREATE TABLE IF NOT EXISTS stone_color_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    color_name VARCHAR(50) NOT NULL,
    price_increase_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default stone colors if they don't exist
INSERT IGNORE INTO stone_color_rates (color_name, price_increase_percentage) VALUES
('Black', 0.00),
('Gray', 10.00),
('Red', 15.00),
('Blue', 20.00);

-- Insert default settings if they don't exist
INSERT IGNORE INTO settings (setting_name, setting_value) VALUES
('commission_rate', '10.00'),
('tax_rate', '13.00'),
('currency', 'USD');
