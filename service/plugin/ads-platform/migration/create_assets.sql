-- Creative assets library
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `erik_assets` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(16) NOT NULL DEFAULT 'image',
    `filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(128) NOT NULL,
    `size` INT UNSIGNED DEFAULT 0,
    `url` VARCHAR(512) NOT NULL,
    `width` INT UNSIGNED DEFAULT 0,
    `height` INT UNSIGNED DEFAULT 0,
    `campaign_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant_type` (`tenant_id`, `type`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
