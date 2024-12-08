-- Create email_settings table
CREATE TABLE IF NOT EXISTS email_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email_provider ENUM('gmail', 'outlook') NOT NULL,
    email_address VARCHAR(255) NOT NULL,
    access_token TEXT,
    refresh_token TEXT,
    token_expires DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create email_tracking table
CREATE TABLE IF NOT EXISTS email_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    company_id INT,
    email_settings_id INT,
    message_id VARCHAR(255),
    subject VARCHAR(255),
    sender VARCHAR(255),
    recipients TEXT,
    content TEXT,
    sent_date DATETIME,
    thread_id VARCHAR(255),
    status ENUM('sent', 'received', 'read', 'replied') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (email_settings_id) REFERENCES email_settings(id) ON DELETE CASCADE,
    INDEX (message_id),
    INDEX (thread_id)
);

-- Create email_templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    variables JSON,
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create email_queue table
CREATE TABLE IF NOT EXISTS email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT,
    customer_id INT,
    company_id INT,
    email_settings_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    recipients TEXT NOT NULL,
    cc TEXT,
    bcc TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    scheduled_time DATETIME,
    sent_time DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (email_settings_id) REFERENCES email_settings(id) ON DELETE CASCADE
);
