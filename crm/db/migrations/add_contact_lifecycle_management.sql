-- First create the companies table if it doesn't exist
CREATE TABLE IF NOT EXISTS companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100),
    website VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    employee_count VARCHAR(20),
    annual_revenue DECIMAL(15,2),
    city VARCHAR(100),
    state VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create lifecycle stages table if it doesn't exist
CREATE TABLE IF NOT EXISTS lifecycle_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default lifecycle stages if not exists
INSERT IGNORE INTO lifecycle_stages (name, description) VALUES
    ('Lead', 'Initial contact or potential customer'),
    ('Marketing Qualified Lead', 'Lead that has shown interest through marketing activities'),
    ('Sales Qualified Lead', 'Lead that has been qualified by sales team'),
    ('Opportunity', 'Active sales opportunity'),
    ('Customer', 'Active customer'),
    ('Churned', 'Former customer');

-- Custom Fields Definition Table
CREATE TABLE IF NOT EXISTS custom_fields_def (
    id INT PRIMARY KEY AUTO_INCREMENT,
    field_name VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'select', 'checkbox') NOT NULL,
    field_options TEXT,
    required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Custom Field Values Table
CREATE TABLE IF NOT EXISTS custom_field_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    field_id INT,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES custom_fields_def(id) ON DELETE CASCADE
);

-- Now modify the customers table
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS company_id INT,
ADD COLUMN IF NOT EXISTS job_title VARCHAR(100),
ADD COLUMN IF NOT EXISTS lifecycle_stage_id INT DEFAULT 1;

-- Add company foreign key if it doesn't exist
SELECT COUNT(1) INTO @fk_exists
FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = DATABASE()
AND TABLE_NAME = 'customers'
AND CONSTRAINT_NAME = 'customers_company_fk';

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE customers ADD CONSTRAINT customers_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add lifecycle stage foreign key if it doesn't exist
SELECT COUNT(1) INTO @fk_exists
FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = DATABASE()
AND TABLE_NAME = 'customers'
AND CONSTRAINT_NAME = 'customers_lifecycle_fk';

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE customers ADD CONSTRAINT customers_lifecycle_fk FOREIGN KEY (lifecycle_stage_id) REFERENCES lifecycle_stages(id) ON DELETE SET NULL',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Activity Timeline Table
CREATE TABLE IF NOT EXISTS activity_timeline (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Lead Scoring Rules Table
DROP TABLE IF EXISTS lead_scoring_rules;
CREATE TABLE lead_scoring_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    rule_type VARCHAR(50) NOT NULL,
    rule_condition TEXT NOT NULL,
    score_value INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default lead scoring rules
INSERT INTO lead_scoring_rules (rule_name, rule_type, rule_condition, score_value) VALUES
    ('Email Open', 'email_engagement', 'Opens marketing email', 1),
    ('Website Visit', 'web_engagement', 'Visits website', 2),
    ('Form Submission', 'form_submission', 'Submits a form', 5),
    ('Meeting Scheduled', 'sales_engagement', 'Schedules a meeting', 10);
