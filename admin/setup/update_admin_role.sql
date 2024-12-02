-- First, remove existing role assignments for the user
DELETE ur FROM user_roles ur
INNER JOIN users u ON ur.user_id = u.id
WHERE u.email = 'info@theangelstones.com';

-- Then assign super_admin role
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'info@theangelstones.com'
AND r.name = 'super_admin';

