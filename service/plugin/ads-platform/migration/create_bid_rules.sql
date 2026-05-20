-- Auto-bidding rule engine tables
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `erik_bid_rules` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `metric` VARCHAR(32) NOT NULL,
    `condition` VARCHAR(16) NOT NULL,
    `threshold` DECIMAL(12,2) NOT NULL,
    `scope` VARCHAR(32) DEFAULT 'tenant',
    `platform` VARCHAR(32) NULL,
    `campaign_id` BIGINT UNSIGNED NULL,
    `action_type` VARCHAR(32) NOT NULL,
    `adjust_step` INT DEFAULT 0,
    `budget_min` BIGINT DEFAULT 0,
    `budget_max` BIGINT DEFAULT 0,
    `cooldown_minutes` INT DEFAULT 60,
    `enabled` TINYINT DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_enabled` (`tenant_id`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_bid_logs` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `rule_id` BIGINT UNSIGNED NOT NULL,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `campaign_id` BIGINT UNSIGNED NOT NULL,
    `metric_value` DECIMAL(12,2) NOT NULL,
    `old_budget` BIGINT DEFAULT 0,
    `new_budget` BIGINT DEFAULT 0,
    `action_type` VARCHAR(32) NOT NULL,
    `old_status` VARCHAR(32) NULL,
    `new_status` VARCHAR(32) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rule` (`rule_id`),
    INDEX `idx_campaign` (`campaign_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
