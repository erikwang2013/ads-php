-- Notifications table for web channel alerts
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `erik_notifications` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,
    `type` VARCHAR(32) NOT NULL DEFAULT 'alert',
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT,
    `is_read` TINYINT DEFAULT 0,
    `rule_id` BIGINT UNSIGNED DEFAULT NULL,
    `log_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant_read` (`tenant_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
