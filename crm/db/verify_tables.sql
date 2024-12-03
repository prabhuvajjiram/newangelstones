-- Create roles table if not exists
CREATE TABLE IF NOT EXISTS `roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create permissions table if not exists
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create role_permissions table if not exists
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` int(11) NOT NULL,
    `permission_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`,`permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create user_roles table if not exists
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` int(11) NOT NULL,
    `role_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`,`role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles if they don't exist
INSERT IGNORE INTO `roles` (`name`, `description`) VALUES
('admin', 'Administrator with full access'),
('staff', 'Staff member with limited access');

-- Insert default permissions if they don't exist
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('manage_users', 'Can manage users'),
('manage_quotes', 'Can manage quotes'),
('view_reports', 'Can view reports');

-- Assign permissions to admin role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM roles r 
CROSS JOIN permissions p 
WHERE r.name = 'admin';

-- Assign basic permissions to staff role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM roles r 
CROSS JOIN permissions p 
WHERE r.name = 'staff' 
AND p.name IN ('manage_quotes', 'view_reports');

-- Update users table to ensure all required fields exist
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `google_id` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `oauth_provider` enum('google','local') DEFAULT 'local',
ADD COLUMN IF NOT EXISTS `oauth_token` text,
ADD COLUMN IF NOT EXISTS `active` tinyint(1) NOT NULL DEFAULT 1,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL;
