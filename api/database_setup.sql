-- Promotions Management System Database Schema
-- Run this SQL in your EXISTING database
-- This will add promotions tables to your current database

-- Promotions table
CREATE TABLE IF NOT EXISTS promotions (
    id VARCHAR(50) PRIMARY KEY,
    type ENUM('product', 'event') NOT NULL DEFAULT 'product',
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(500) NOT NULL,
    link_url VARCHAR(500) DEFAULT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    priority INT DEFAULT 1,
    enabled TINYINT(1) DEFAULT 1,
    
    -- Pricing fields
    special_price DECIMAL(10,2) DEFAULT NULL,
    list_price DECIMAL(10,2) DEFAULT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    display_format VARCHAR(50) DEFAULT NULL,
    
    -- Product details
    product_code VARCHAR(50) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    tablet TEXT DEFAULT NULL,
    base_info TEXT DEFAULT NULL,
    features TEXT DEFAULT NULL,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100) DEFAULT 'Angel',
    archived TINYINT(1) DEFAULT 0,
    archived_at DATETIME DEFAULT NULL,
    
    INDEX idx_enabled (enabled),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_priority (priority),
    INDEX idx_type (type),
    INDEX idx_archived (archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table (simple authentication)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (username: Angel, password: AngelStones@2025)
-- IMPORTANT: Change this password after first login!
INSERT INTO admin_users (username, password_hash, email) 
VALUES ('Angel', '$2y$12$UNuFmXrArmxLlWmfi7aa/eutc8EiJuf9SVunMBG8uIZaKEXUiqyRG', 'info@theangelstones.com')
ON DUPLICATE KEY UPDATE username=username;

-- Sample promotion data
INSERT INTO promotions (
    id, type, title, subtitle, description, image_url, 
    start_date, end_date, priority, enabled,
    special_price, list_price, currency, display_format,
    product_code, color, tablet, base_info, features
) VALUES (
    'promo_bahama_blue_2025',
    'product',
    'Special A: Bahama Blue',
    'AG-647A Monument Special',
    'Serp Top, P5 with carved Antique Finished Grapes on ends',
    'https://theangelstones.com/images/promotions/bahama_blue_special.jpg',
    '2025-01-13 00:00:00',
    '2025-01-20 23:59:59',
    1,
    1,
    2038.00,
    2646.00,
    'USD',
    '$2,038 nett',
    'AG-647A',
    'Bahama Blue',
    '3-6 X 0-8 X 2-2 AG-647A, Serp Top, P5',
    '4-6 x 1-2 x 0-8 PFT, 2" Polished Margin, BRP',
    'Carved Antique Finished Grapes on ends as shown'
) ON DUPLICATE KEY UPDATE id=id;

-- Create uploads directory (run this after database setup)
-- mkdir -p /path/to/your/website/images/promotions
-- chmod 755 /path/to/your/website/images/promotions
