-- Conversion tracking and multi-touch attribution tables
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `erik_conversions` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `order_id` VARCHAR(128) NOT NULL,
    `value` DECIMAL(12,2) DEFAULT 0,
    `currency` VARCHAR(8) DEFAULT 'CNY',
    `conversion_time` DATETIME NOT NULL,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_order` (`tenant_id`, `platform`, `order_id`),
    INDEX `idx_tenant_time` (`tenant_id`, `conversion_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_attribution_results` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `conversion_id` BIGINT UNSIGNED NOT NULL,
    `model` VARCHAR(32) NOT NULL,
    `campaign_id` BIGINT UNSIGNED NOT NULL,
    `credit` DECIMAL(12,2) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_conversion_model` (`conversion_id`, `model`),
    INDEX `idx_campaign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
