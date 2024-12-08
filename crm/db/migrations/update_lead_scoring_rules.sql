-- Drop the existing lead scoring rules table
DROP TABLE IF EXISTS lead_scoring_rules;

-- Create the updated lead scoring rules table
CREATE TABLE lead_scoring_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    condition_field VARCHAR(50) NOT NULL,
    condition_operator ENUM('equals', 'not_equals', 'contains', 'greater_than', 'less_than') NOT NULL,
    condition_value VARCHAR(255) NOT NULL,
    score_value INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default lead scoring rules with proper conditions
INSERT INTO lead_scoring_rules 
(rule_name, condition_field, condition_operator, condition_value, score_value) 
VALUES
    ('Email Domain Check', 'email', 'contains', '@company.com', 5),
    ('Job Title Executive', 'job_title', 'contains', 'CEO', 10),
    ('Large Company', 'employee_count', 'greater_than', '100', 8),
    ('High Revenue', 'annual_revenue', 'greater_than', '1000000', 10),
    ('Industry Tech', 'industry', 'equals', 'Technology', 5),
    ('Recent Contact', 'last_contact_date', 'greater_than', DATE_SUB(NOW(), INTERVAL 30 DAY), 3);
