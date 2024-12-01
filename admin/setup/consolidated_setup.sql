-- Drop database if exists and create new one
DROP DATABASE IF EXISTS angelstones_quotes_new;
CREATE DATABASE angelstones_quotes_new;
USE angelstones_quotes_new;

-- Create users table first (referenced by other tables)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insert default admin user (password: P@ssword1)
INSERT INTO users (username, password, email, role, active) VALUES
('admin', '$2y$10$vs3H7J.kjRb4H36xZP.QU.YAjhAPQJV0zW2PCTQFOpxvuAH2.v.UG', 'admin@example.com', 'admin', TRUE);

-- Create lead sources table for better lead tracking
CREATE TABLE lead_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default lead sources
INSERT INTO lead_sources (source_name, description) VALUES
('Website', 'Leads from company website'),
('Referral', 'Customer referrals'),
('Google Ads', 'Google advertising campaigns'),
('Facebook', 'Facebook social media'),
('Instagram', 'Instagram social media'),
('Trade Show', 'Trade show contacts'),
('Direct Mail', 'Direct mail campaigns'),
('Cold Call', 'Cold calling campaigns');

-- Create campaigns table for marketing campaigns
CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('email', 'sms', 'social', 'print', 'other') NOT NULL,
    status ENUM('draft', 'scheduled', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE,
    end_date DATE,
    budget DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    target_audience TEXT,
    success_metrics TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

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
('Grey', 0.00);

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
    lead_score INT DEFAULT 0,
    last_contact_date TIMESTAMP NULL,
    lead_source_id INT NULL,
    last_campaign_id INT NULL,
    preferred_contact_method ENUM('email', 'phone', 'sms', 'mail') DEFAULT 'email',
    budget_range VARCHAR(50),
    decision_timeframe VARCHAR(50),
    status ENUM('active', 'inactive', 'potential', 'converted') DEFAULT 'potential',
    total_quotes INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_source_id) REFERENCES lead_sources(id),
    FOREIGN KEY (last_campaign_id) REFERENCES campaigns(id)
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
    quote_number VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT,
    customer_email VARCHAR(100),
    total_amount DECIMAL(10, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    commission_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    valid_until DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Create quote items table
CREATE TABLE quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    product_type VARCHAR(50) NOT NULL,
    model VARCHAR(20) NOT NULL,
    size VARCHAR(20) NOT NULL,
    color_id INT NOT NULL,
    length DECIMAL(10, 2) NOT NULL,
    breadth DECIMAL(10, 2) NOT NULL,
    sqft DECIMAL(10, 2) NOT NULL,
    cubic_feet DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
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

-- Create customer notes table for enhanced CRM
CREATE TABLE customer_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    note_type ENUM('general', 'follow_up', 'quote', 'payment') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create email templates table for automated communications
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    template_type ENUM('quote', 'follow_up', 'welcome', 'general') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default email templates
INSERT INTO email_templates (name, subject, body, template_type) VALUES
('Quote Follow-up', 'Following up on your recent quote', 'Dear {customer_name},\n\nThank you for your interest in our products. I wanted to follow up regarding the quote ({quote_number}) we prepared for you on {quote_date}.\n\nPlease let me know if you have any questions or if you would like to proceed with the order.\n\nBest regards,\n{user_name}', 'follow_up'),
('New Quote', 'Your Quote from Angel Stones', 'Dear {customer_name},\n\nThank you for your interest in our products. Please find attached your quote ({quote_number}).\n\nIf you have any questions, please don''t hesitate to contact us.\n\nBest regards,\n{user_name}', 'quote');

-- Create customer communications table for tracking all interactions
CREATE TABLE customer_communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('email', 'phone', 'meeting', 'other') NOT NULL,
    subject VARCHAR(200),
    content TEXT,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create tasks table for task management
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    customer_id INT NULL,
    quote_id INT NULL,
    user_id INT NOT NULL,
    created_by INT NOT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create campaign_results table for tracking campaign performance
CREATE TABLE campaign_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    metric_name VARCHAR(50) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    date_recorded DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
);

-- Create customer_documents table for file management
CREATE TABLE customer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    quote_id INT,
    document_type ENUM('quote', 'contract', 'design', 'invoice', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    uploaded_by INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Create customer_preferences table
CREATE TABLE customer_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    preference_key VARCHAR(50) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    UNIQUE KEY unique_customer_preference (customer_id, preference_key)
);

-- Create reminder_settings table
CREATE TABLE reminder_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reminder_type ENUM('quote_follow_up', 'task_due', 'campaign_start', 'customer_birthday') NOT NULL,
    days_before INT NOT NULL DEFAULT 1,
    is_email BOOLEAN DEFAULT TRUE,
    is_notification BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create SERTOP products table
CREATE TABLE sertop_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model VARCHAR(100) NOT NULL,
    size_inches DECIMAL(10,2) NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sertop products with correct prices
INSERT INTO sertop_products (model, size_inches, base_price) VALUES
('P1', 8, 57.72),
('P2', 8, 57.72),
('P3', 8, 61.05),
('P4', 8, 61.05),
('P5', 8, 61.05),
('P1', 6, 44.40),
('P2', 6, 44.40),
('P3', 6, 46.62),
('P4', 6, 46.62),
('P5', 6, 46.62);

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
('BASE-8-P1', 'P1', 8, FALSE, 57.72),
('BASE-8-PM', 'P/M', 8, FALSE, 64.38),
('BASE-6-P1', 'P1', 6, FALSE, 44.44),
('BASE-6-PM', 'P/M', 6, FALSE, 48.84),
('BASE-10-P1', 'P1', 10, FALSE, 68.82),
('BASE-10-PM', 'P/M', 10, FALSE, 82.14);

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
('MAR-3-P1', 'P1', 3.0, 26.64),
('MAR-4-P1', 'P1', 4.0, 32.19);

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
('SLANT-P2', 'P2', 73.26),
('SLANT-P3', 'P3', 77.77),
('SLANT-P4', 'P4', 81.03),
('SLANT-P5', 'P5', 83.89);

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

-- Create email_settings table for Google Workspace integration
CREATE TABLE email_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create email_queue table for handling email sending
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template_id INT NULL,
    customer_id INT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT,
    scheduled_for TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create email_attachments table
CREATE TABLE email_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES email_queue(id) ON DELETE CASCADE
);

-- Create email_logs table for tracking email history
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    event_type ENUM('queued', 'sent', 'failed', 'opened', 'clicked', 'bounced') NOT NULL,
    event_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES email_queue(id) ON DELETE CASCADE
);

-- Create email_settings table
CREATE TABLE IF NOT EXISTS email_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create email_queue table
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    customer_id INT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT,
    scheduled_for TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create email_attachments table
CREATE TABLE IF NOT EXISTS email_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES email_queue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create email_logs table
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    event_type ENUM('queued', 'sent', 'failed', 'opened', 'clicked', 'bounced') NOT NULL,
    event_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES email_queue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    customer_id INT,
    assigned_to INT,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer Communications table
CREATE TABLE IF NOT EXISTS customer_communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    type ENUM('email', 'phone', 'meeting', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
