-- Consolidated database setup file for Angel Stones
-- Generated on: 2024

-- Create and setup the database
DROP DATABASE IF EXISTS angelstones_quotes_new;
CREATE DATABASE angelstones_quotes_new;
USE angelstones_quotes_new;

-- Create users table first (referenced by other tables)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: P@ssword1)
INSERT INTO users (username, password, email) VALUES
('admin', '$2y$10$vs3H7J.kjRb4H36xZP.QU.YAjhAPQJV0zW2PCTQFOpxvuAH2.v.UG', 'admin@example.com');

-- Create stone colors table
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
('Star Galaxy Black', 40.00),
('Bahama Blue', 0.00),
('NH Red', 20.00),
('Cats Eye', 20.00),
('Brown Wood', 40.00),
('SF Impala', 65.00),
('Blue Pearl', 100.00),
('Emeral Pearl', 100.00),
('rainforest Green', 45.00),
('Brazil Gold', 35.00),
('Grey', 0.00),
('Brown', 5.00),
('Green', 15.00);

-- Create customers table (referenced by quotes)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

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
('Special', 20.00),
('No Commision', 0.00);

-- Create quotes table
CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    requested_by VARCHAR(100),
    project_name VARCHAR(100),
    total_amount DECIMAL(10, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) NOT NULL,
    commission_amount DECIMAL(10, 2) NOT NULL,
    pdf_file VARCHAR(255),
    length DECIMAL(10, 2),
    breadth DECIMAL(10, 2),
    width_polish DECIMAL(10, 2),
    color VARCHAR(100),
    quantity INT,
    sertop_type VARCHAR(50),
    sertop_price DECIMAL(10, 2),
    total_area DECIMAL(10, 2),
    price_per_sqft DECIMAL(10, 2),
    width_polish_cost DECIMAL(10, 2),
    sertop_total DECIMAL(10, 2),
    subtotal DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE
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
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (color_id) REFERENCES stone_color_rates(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Create quote status history table
CREATE TABLE quote_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Create follow ups table
CREATE TABLE follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    customer_id INT,
    follow_up_date DATE NOT NULL,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

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
INSERT INTO sertop_products (product_code, model, size_inches, base_price) VALUES
('SER-8-P1', 'P1', 8, 57.72),
('SER-8-P2', 'P2', 8, 58.55),
('SER-8-P3', 'P3', 8, 59.38),
('SER-8-P4', 'P4', 8, 60.22),
('SER-8-P5', 'P5', 8, 61.05),
('SER-6-P1', 'P1', 6, 43.29),
('SER-6-P2', 'P2', 6, 43.91),
('SER-6-P3', 'P3', 6, 44.54),
('SER-6-P4', 'P4', 6, 45.17),
('SER-6-P5', 'P5', 6, 45.79);

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
INSERT INTO base_products (product_code, model, size_inches, is_premium, base_price) VALUES
('BASE-8-P1', 'P1', 8, FALSE, 72.15),
('BASE-8-PM', 'P/M', 8, FALSE, 73.19),
('BASE-6-P1', 'P1', 6, FALSE, 54.11),
('BASE-6-PM', 'P/M', 6, FALSE, 54.89);

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
INSERT INTO marker_products (product_code, model, square_feet, base_price) VALUES
('MAR-3-P1', 'P1', 3.0, 28.86),
('MAR-4-P1', 'P1', 4.0, 38.48);

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
INSERT INTO slant_products (product_code, model, base_price) VALUES
('SLANT-P2', 'P2', 86.58),
('SLANT-P3', 'P3', 87.83),
('SLANT-P4', 'P4', 89.08),
('SLANT-P5', 'P5', 90.33);

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
