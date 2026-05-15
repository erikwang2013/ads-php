CREATE TABLE IF NOT EXISTS `erik_alert_rules` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `metric` VARCHAR(32) NOT NULL,
    `condition` VARCHAR(16) NOT NULL,
    `threshold` DECIMAL(12,2) NOT NULL,
    `scope` VARCHAR(32) DEFAULT 'tenant',
    `platform` VARCHAR(32) NULL,
    `campaign_id` BIGINT UNSIGNED NULL,
    `check_interval` INT DEFAULT 5,
    `channels` JSON NULL,
    `enabled` TINYINT DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_enabled` (`tenant_id`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erik_alert_logs` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `rule_id` BIGINT UNSIGNED NOT NULL,
    `rule_name` VARCHAR(100) NOT NULL,
    `metric` VARCHAR(32) NOT NULL,
    `current_value` DECIMAL(12,2) NOT NULL,
    `threshold` DECIMAL(12,2) NOT NULL,
    `condition` VARCHAR(16) NOT NULL,
    `status` ENUM('triggered','acknowledged','resolved') DEFAULT 'triggered',
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant_status` (`tenant_id`, `status`),
    INDEX `idx_rule` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
