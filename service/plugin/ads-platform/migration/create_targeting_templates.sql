-- Audience targeting templates
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `erik_targeting_templates` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `platform` VARCHAR(32) NOT NULL DEFAULT '',
    `targeting` JSON NOT NULL,
    `is_shared` TINYINT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_platform` (`tenant_id`, `platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
