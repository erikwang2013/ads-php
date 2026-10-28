-- ============================================================================
-- Ads-PHP 统一安装 SQL
-- 8 张业务表（ads_*，无框架表、无种子数据），按依赖顺序排列
-- 目标数据库: MySQL 5.7+ / MariaDB 10.3+
-- 字符集: utf8mb4
-- ============================================================================

-- ============================================================================
-- 1. 平台账号 (ads-account)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ads_platform_accounts` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `platform` VARCHAR(32) NOT NULL,
    `account_id_on_platform` VARCHAR(128) NOT NULL,
    `account_name` VARCHAR(255) DEFAULT NULL,
    `access_token` TEXT DEFAULT NULL,
    `refresh_token` VARCHAR(512) DEFAULT NULL,
    `token_expires_at` DATETIME DEFAULT NULL,
    `status` TINYINT DEFAULT 1,
    `sync_enabled` TINYINT DEFAULT 1,
    `last_sync_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_account` (`platform`, `account_id_on_platform`),
    INDEX `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ads_auth_tokens` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `platform` VARCHAR(32) NOT NULL,
    `state` VARCHAR(64) NOT NULL,
    `redirect_uri` VARCHAR(512) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 2. 投放与指标数据表 (平台同步，只读)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ads_campaigns` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `platform_account_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `platform_campaign_id` VARCHAR(128) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `daily_budget` BIGINT DEFAULT 0,
    `total_budget` BIGINT DEFAULT 0,
    `status` VARCHAR(32) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `extra` JSON NULL,
    `synced_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_campaign` (`platform_account_id`, `platform_campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ads_ad_groups` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `campaign_id` BIGINT UNSIGNED NOT NULL,
    `platform_adgroup_id` VARCHAR(128) NOT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT NULL,
    `bid_amount` BIGINT DEFAULT 0,
    `bid_type` VARCHAR(32) DEFAULT NULL,
    `targeting` JSON NULL,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_adgroup` (`campaign_id`, `platform_adgroup_id`),
    INDEX `idx_campaign_status` (`campaign_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ads_creatives` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `ad_group_id` BIGINT UNSIGNED NOT NULL,
    `platform_creative_id` VARCHAR(128) NOT NULL,
    `title` VARCHAR(500) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `media_type` VARCHAR(32) DEFAULT NULL,
    `media_urls` JSON NULL,
    `landing_url` VARCHAR(2048) DEFAULT NULL,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_creative` (`ad_group_id`, `platform_creative_id`),
    INDEX `idx_adgroup_media` (`ad_group_id`, `media_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ads_report_metrics` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `platform_account_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `campaign_id` BIGINT UNSIGNED DEFAULT NULL,
    `ad_group_id` BIGINT UNSIGNED DEFAULT NULL,
    `creative_id` BIGINT UNSIGNED DEFAULT NULL,
    `date` DATE NOT NULL,
    `granularity` VARCHAR(16) DEFAULT 'daily',
    `cost` BIGINT DEFAULT 0,
    `impressions` BIGINT DEFAULT 0,
    `clicks` BIGINT DEFAULT 0,
    `conversions` DECIMAL(10,2) DEFAULT 0,
    `ctr` DECIMAL(10,6) DEFAULT 0,
    `cpm` DECIMAL(10,2) DEFAULT 0,
    `cpc` DECIMAL(10,2) DEFAULT 0,
    `cvr` DECIMAL(10,6) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_report` (`platform`, `platform_account_id`, `campaign_id`, `ad_group_id`, `creative_id`, `date`, `granularity`),
    INDEX `idx_date` (`date`),
    INDEX `idx_campaign_date` (`campaign_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 3. 素材库 (ads-platform / assets)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ads_assets` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `type` VARCHAR(16) NOT NULL DEFAULT 'image',
    `filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(128) NOT NULL,
    `size` INT UNSIGNED DEFAULT 0,
    `url` VARCHAR(512) NOT NULL,
    `width` INT UNSIGNED DEFAULT 0,
    `height` INT UNSIGNED DEFAULT 0,
    `campaign_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 4. 同步错误 (ads-task)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ads_sync_errors` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `platform_account_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `error_message` TEXT,
    `retry_count` INT DEFAULT 0,
    `last_error` TEXT,
    `next_retry_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_retry` (`retry_count`, `next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
