-- Admin RBAC tables
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100),
    `email` VARCHAR(100),
    `avatar` VARCHAR(255),
    `role_id` BIGINT UNSIGNED DEFAULT 0,
    `status` TINYINT DEFAULT 1,
    `last_login_at` DATETIME,
    `last_login_ip` VARCHAR(45),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admin_users (id, username, password, name, role_id) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1)
ON DUPLICATE KEY UPDATE username=VALUES(username);

CREATE TABLE IF NOT EXISTS `admin_roles` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `permissions` JSON,
    `description` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admin_roles (id, name, slug, permissions) VALUES
(1, '超级管理员', 'super_admin', '["*"]'),
(2, '运营经理', 'ops_manager', '["dashboard","campaigns","reports","alerts","accounts"]'),
(3, '数据分析师', 'analyst', '["dashboard","reports"]')
ON DUPLICATE KEY UPDATE name=VALUES(name);

CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `resource` VARCHAR(50) NOT NULL,
    `resource_id` VARCHAR(50),
    `detail` JSON,
    `ip` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
