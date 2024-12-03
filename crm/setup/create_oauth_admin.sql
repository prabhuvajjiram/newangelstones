-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Create user_roles table
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Add OAuth fields to users table if they don't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS oauth_provider ENUM('google', 'local') DEFAULT 'local',
ADD COLUMN IF NOT EXISTS oauth_token TEXT NULL;

-- Clear existing data (in correct order)
SET FOREIGN_KEY_CHECKS=0;
DELETE FROM role_permissions;
DELETE FROM user_roles;
DELETE FROM roles;
DELETE FROM permissions;
SET FOREIGN_KEY_CHECKS=1;

-- Insert roles
INSERT INTO roles (name, description) VALUES
('super_admin', 'Super Administrator with full system access'),
('admin', 'Administrator with access to most features'),
('staff', 'Staff member with basic access');

-- Insert permissions
INSERT INTO permissions (name, description) VALUES
('manage_settings', 'Can modify system settings'),
('manage_users', 'Can manage user accounts'),
('manage_quotes', 'Can manage quotes'),
('view_quotes', 'Can view quotes'),
('create_quotes', 'Can create new quotes'),
('manage_crm', 'Can manage CRM data'),
('manage_products', 'Can manage products'),
('view_reports', 'Can view reports');

-- Assign permissions to super_admin (all permissions)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'super_admin'),
    id
FROM permissions;

-- Assign permissions to admin (everything except settings and user management)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id
FROM permissions
WHERE name NOT IN ('manage_settings', 'manage_users');

-- Assign permissions to staff (only quote and CRM related)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'staff'),
    id
FROM permissions
WHERE name IN ('view_quotes', 'create_quotes', 'manage_crm');
