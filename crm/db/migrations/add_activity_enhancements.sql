-- Activity Categories Table
CREATE TABLE IF NOT EXISTS activity_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(20),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default activity categories
INSERT INTO activity_categories (name, icon, color, description) VALUES
    ('Email', 'envelope', '#007bff', 'Email communications'),
    ('Phone', 'phone', '#28a745', 'Phone calls and messages'),
    ('Meeting', 'calendar', '#ffc107', 'In-person or virtual meetings'),
    ('Note', 'sticky-note', '#6c757d', 'General notes and comments'),
    ('Task', 'check-square', '#17a2b8', 'Tasks and to-dos'),
    ('Document', 'file', '#dc3545', 'Document related activities'),
    ('System', 'cog', '#6610f2', 'System generated activities');

-- Add new columns to activity_timeline if they don't exist
-- Check and add category_id
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'activity_timeline' 
AND COLUMN_NAME = 'category_id';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_timeline 
    ADD COLUMN category_id INT AFTER activity_type,
    ADD FOREIGN KEY (category_id) REFERENCES activity_categories(id)',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add importance
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'activity_timeline' 
AND COLUMN_NAME = 'importance';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_timeline 
    ADD COLUMN importance ENUM("low", "medium", "high") DEFAULT "medium"',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add is_private
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'activity_timeline' 
AND COLUMN_NAME = 'is_private';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_timeline 
    ADD COLUMN is_private BOOLEAN DEFAULT FALSE',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add associated_company_id
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'activity_timeline' 
AND COLUMN_NAME = 'associated_company_id';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_timeline 
    ADD COLUMN associated_company_id INT,
    ADD FOREIGN KEY (associated_company_id) REFERENCES companies(id)',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add tags
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'activity_timeline' 
AND COLUMN_NAME = 'tags';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_timeline 
    ADD COLUMN tags JSON',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add title
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'activity_timeline' 
AND COLUMN_NAME = 'title';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_timeline 
    ADD COLUMN title VARCHAR(255)',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Activity Analytics Table
CREATE TABLE IF NOT EXISTS activity_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    category_id INT,
    customer_id INT,
    company_id INT,
    activity_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES activity_categories(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    UNIQUE KEY date_category_customer (date, category_id, customer_id)
);

-- Activity Export Logs
CREATE TABLE IF NOT EXISTS activity_export_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    export_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    filter_criteria JSON,
    record_count INT,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    file_path VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_activity_timeline_category ON activity_timeline(category_id);
CREATE INDEX IF NOT EXISTS idx_activity_timeline_importance ON activity_timeline(importance);
CREATE INDEX IF NOT EXISTS idx_activity_analytics_date ON activity_analytics(date);
