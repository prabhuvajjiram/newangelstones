-- Remove test admin user
DELETE FROM users WHERE email = 'admin@example.com';

-- Ensure proper role assignments for existing users
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'info@theangelstones.com'
AND r.name = 'admin'
AND NOT EXISTS (
    SELECT 1 FROM user_roles ur 
    WHERE ur.user_id = u.id AND ur.role_id = r.id
);

-- Add basic permissions
INSERT IGNORE INTO permissions (name, description) VALUES
('manage_users', 'Can manage users'),
('manage_quotes', 'Can manage quotes'),
('view_reports', 'Can view reports'),
('manage_products', 'Can manage products'),
('manage_customers', 'Can manage customers');

-- Assign all permissions to admin role
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'admin';

-- Assign basic permissions to staff role
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'staff'
AND p.name IN ('manage_quotes', 'view_reports', 'manage_customers');
