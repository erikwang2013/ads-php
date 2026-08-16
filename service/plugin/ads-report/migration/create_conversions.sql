-- Conversion tracking and multi-touch attribution tables
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

CREATE TABLE IF NOT EXISTS `erik_conversions` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `campaign_id` BIGINT UNSIGNED NULL COMMENT '关联 erik_campaigns.id（转化回传 API 必填）',
    `order_id` VARCHAR(128) NOT NULL,
    `value` DECIMAL(12,2) DEFAULT 0 COMMENT '金额，单位：分（与 cost 口径一致）',
    `currency` VARCHAR(8) DEFAULT 'CNY',
    `channel` VARCHAR(32) DEFAULT 'api' COMMENT '转化来源渠道（api 回传/埋点/手动）',
    `conversion_time` DATETIME NOT NULL,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_order` (`tenant_id`, `platform`, `order_id`),
    INDEX `idx_tenant_time` (`tenant_id`, `conversion_time`),
    INDEX `idx_campaign` (`campaign_id`)
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
