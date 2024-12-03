-- First, remove the super_admin role and any assignments
DELETE FROM user_roles WHERE role_id IN (SELECT id FROM roles WHERE name = 'super_admin');
DELETE FROM roles WHERE name = 'super_admin';

-- Ensure admin and staff roles exist
INSERT IGNORE INTO roles (name, description) VALUES 
('admin', 'Administrator with full access to all features'),
('staff', 'Staff member with access to CRM, quotes, and customers');

-- Get the admin role ID
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'admin');

-- Update the specific user to have admin role
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, @admin_role_id
FROM users u
WHERE u.email = 'info@theangelstones.com';
