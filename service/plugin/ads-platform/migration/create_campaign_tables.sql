CREATE TABLE IF NOT EXISTS `erik_campaigns` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
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
    UNIQUE KEY `uk_platform_campaign` (`platform_account_id`, `platform_campaign_id`),
    INDEX `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_ad_groups` (
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
    UNIQUE KEY `uk_platform_adgroup` (`campaign_id`, `platform_adgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_creatives` (
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
    UNIQUE KEY `uk_platform_creative` (`ad_group_id`, `platform_creative_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_report_metrics` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
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
    UNIQUE KEY `uk_report` (`tenant_id`, `platform`, `platform_account_id`, `campaign_id`, `ad_group_id`, `creative_id`, `date`, `granularity`),
    INDEX `idx_date` (`date`),
    INDEX `idx_campaign_date` (`campaign_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_report_extras` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `report_metric_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `extra` JSON NULL,
    FOREIGN KEY (`report_metric_id`) REFERENCES `erik_report_metrics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
