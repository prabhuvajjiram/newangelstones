-- First ensure the roles exist
INSERT IGNORE INTO roles (name) VALUES ('super_admin'), ('admin'), ('staff');

-- Get the role IDs
SET @super_admin_role_id = (SELECT id FROM roles WHERE name = 'super_admin');
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'admin');

-- Update the specific user to have super_admin role
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, @super_admin_role_id
FROM users u
WHERE u.email = 'info@theangelstones.com';

-- Also give them admin role for full access
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, @admin_role_id
FROM users u
WHERE u.email = 'info@theangelstones.com';
