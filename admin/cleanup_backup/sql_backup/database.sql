CREATE DATABASE angelstones_quotes;
DROP DATABASE IF EXISTS angelstones_quotes;
USE angelstones_quotes;

CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS labor_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    rate_per_hour DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS overhead_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    percentage DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stone_color_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    color_name VARCHAR(100) NOT NULL,
    price_increase_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS commission_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    length DECIMAL(10, 2) NOT NULL,
    breadth DECIMAL(10, 2) NOT NULL,
    width_polish DECIMAL(10, 2) NOT NULL,
    color VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    sertop_type ENUM('base', 'slant') NOT NULL,
    sertop_price DECIMAL(10, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) NOT NULL,
    total_area DECIMAL(10, 2) NOT NULL,
    price_per_sqft DECIMAL(10, 2) NOT NULL,
    width_polish_cost DECIMAL(10, 2) NOT NULL,
    sertop_total DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    commission_amount DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quote_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

CREATE TABLE IF NOT EXISTS quote_labor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    labor_id INT NOT NULL,
    hours DECIMAL(10, 2) NOT NULL,
    rate_per_hour DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (labor_id) REFERENCES labor_rates(id)
);

-- Insert commission rates
INSERT INTO commission_rates (rate_name, percentage) VALUES
('Standard', 10.00),
('Premium', 15.00),
('Special', 20.00);
