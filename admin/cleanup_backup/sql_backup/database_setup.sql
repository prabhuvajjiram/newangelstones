-- Create and setup the database
DROP DATABASE IF EXISTS angelstones_quotes_new;
CREATE DATABASE angelstones_quotes_new;
USE angelstones_quotes_new;

-- Create stone colors table with only color name and price increase percentage
CREATE TABLE stone_color_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    color_name VARCHAR(100) NOT NULL,
    price_increase_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert stone colors with their price increase percentages
INSERT INTO stone_color_rates (color_name, price_increase_percentage) VALUES
('Black', 0.00),
('Coffee Brown', 7.00),
('Dark Grey', 5.00),
('English Teak', 12.00),
('Galaxy Black', 15.00),
('Green', 10.00),
('Grey', 3.00),
('Indian Green', 8.00),
('Multi Color', 20.00),
('Red', 18.00),
('Tan Brown', 6.00),
('White', 4.00);

-- Create commission rates table
CREATE TABLE commission_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert commission rates
INSERT INTO commission_rates (rate_name, percentage) VALUES
('Standard', 10.00),
('Premium', 15.00),
('Special', 20.00);

-- Create SERTOP products table
CREATE TABLE sertop_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(10) NOT NULL,
    model VARCHAR(10) NOT NULL,
    size_inches INT NOT NULL,
    length_inches DECIMAL(10, 2) NOT NULL,
    breadth_inches DECIMAL(10, 2) NOT NULL,
    description VARCHAR(100),
    base_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert SERTOP products
INSERT INTO sertop_products (product_code, model, size_inches, length_inches, breadth_inches, description, base_price) VALUES
-- 8-inch variants
('8-P1', 'P1', 8, 24.00, 14.00, '8-inch SERTOP P1', 150.00),
('8-P2', 'P2', 8, 24.00, 14.00, '8-inch SERTOP P2', 160.00),
('8-P3', 'P3', 8, 24.00, 14.00, '8-inch SERTOP P3', 170.00),
('8-P4', 'P4', 8, 24.00, 14.00, '8-inch SERTOP P4', 180.00),
('8-P5', 'P5', 8, 24.00, 14.00, '8-inch SERTOP P5', 190.00),
-- 6-inch variants
('6-P1', 'P1', 6, 20.00, 12.00, '6-inch SERTOP P1', 130.00),
('6-P2', 'P2', 6, 20.00, 12.00, '6-inch SERTOP P2', 140.00),
('6-P3', 'P3', 6, 20.00, 12.00, '6-inch SERTOP P3', 150.00),
('6-P4', 'P4', 6, 20.00, 12.00, '6-inch SERTOP P4', 160.00),
('6-P5', 'P5', 6, 20.00, 12.00, '6-inch SERTOP P5', 170.00);

-- Create BASE products table
CREATE TABLE base_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(10) NOT NULL,
    model VARCHAR(10) NOT NULL,
    size_inches INT NOT NULL,
    length_inches DECIMAL(10, 2) NOT NULL,
    breadth_inches DECIMAL(10, 2) NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    description VARCHAR(100),
    base_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert BASE products
INSERT INTO base_products (product_code, model, size_inches, length_inches, breadth_inches, is_premium, description, base_price) VALUES
('8-P1', 'P1', 8, 24.00, 14.00, FALSE, '8-inch Base P1', 140.00),
('8-PM', 'PM', 8, 24.00, 14.00, TRUE, '8-inch Premium Base', 160.00),
('6-P1', 'P1', 6, 20.00, 12.00, FALSE, '6-inch Base P1', 120.00),
('6-PM', 'PM', 6, 20.00, 12.00, TRUE, '6-inch Premium Base', 140.00),
('10-P1', 'P1', 10, 28.00, 16.00, FALSE, '10-inch Base P1', 180.00),
('10-PM', 'PM', 10, 28.00, 16.00, TRUE, '10-inch Premium Base', 200.00);

-- Create MARKER products table
CREATE TABLE marker_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(10) NOT NULL,
    model VARCHAR(10) NOT NULL,
    square_feet DECIMAL(4, 1) NOT NULL,
    length_inches DECIMAL(10, 2) NOT NULL,
    breadth_inches DECIMAL(10, 2) NOT NULL,
    description VARCHAR(100),
    base_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert MARKER products
INSERT INTO marker_products (product_code, model, square_feet, length_inches, breadth_inches, description, base_price) VALUES
('P1-3SQFT', 'P1', 3.0, 36.00, 12.00, 'Marker P1 - 3 Square Feet', 200.00),
('P1-4SQFT', 'P1', 4.0, 48.00, 12.00, 'Marker P1 - 4 Square Feet', 250.00);

-- Create SLANT products table
CREATE TABLE slant_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(10) NOT NULL,
    model VARCHAR(10) NOT NULL,
    length_inches DECIMAL(10, 2) NOT NULL,
    breadth_inches DECIMAL(10, 2) NOT NULL,
    description VARCHAR(100),
    base_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert SLANT products
INSERT INTO slant_products (product_code, model, length_inches, breadth_inches, description, base_price) VALUES
('P2', 'P2', 24.00, 14.00, 'Slant Product P2', 160.00),
('P3', 'P3', 24.00, 14.00, 'Slant Product P3', 170.00),
('P4', 'P4', 24.00, 14.00, 'Slant Product P4', 180.00),
('P5', 'P5', 24.00, 14.00, 'Slant Product P5', 190.00);

-- Create price components table
CREATE TABLE price_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    base_rate DECIMAL(10, 2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert basic price components
INSERT INTO price_components (component_name, base_rate, description) VALUES 
('Width Polish', 10.00, 'Base rate for width polishing'),
('Edge Polish', 15.00, 'Base rate for edge polishing'),
('Beveling', 20.00, 'Base rate for beveling'),
('Lamination', 25.00, 'Base rate for lamination'),
('Engraving', 30.00, 'Base rate for engraving');

-- Create quotes table
CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    requested_by VARCHAR(100),
    project_name VARCHAR(100),
    total_amount DECIMAL(10, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) NOT NULL,
    commission_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Create quote items table
CREATE TABLE quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    product_type VARCHAR(50) NOT NULL,
    size VARCHAR(20) NOT NULL,
    model VARCHAR(20) NOT NULL,
    color_id INT NOT NULL,
    length DECIMAL(10, 2) NOT NULL,
    breadth DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    price_increase DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (color_id) REFERENCES stone_color_rates(id)
);

-- Create users table for admin access
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Create customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create follow_ups table
CREATE TABLE follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    quote_id INT,
    status ENUM('pending', 'contacted', 'interested', 'not_interested', 'converted', 'cancelled') NOT NULL DEFAULT 'pending',
    follow_up_date DATE NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create quote_status_history table
CREATE TABLE quote_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    status ENUM('created', 'sent', 'viewed', 'accepted', 'rejected', 'expired') NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
