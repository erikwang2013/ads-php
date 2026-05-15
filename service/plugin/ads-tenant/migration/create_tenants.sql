CREATE TABLE IF NOT EXISTS `erik_tenants` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `domain` VARCHAR(255) DEFAULT NULL,
    `db_type` ENUM('shared','dedicated') DEFAULT 'shared',
    `db_config` JSON NULL,
    `plan` ENUM('free','pro','enterprise') DEFAULT 'free',
    `status` TINYINT DEFAULT 1,
    INDEX `idx_domain_status` (`domain`, `status`),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `erik_tenants` (`id`, `name`, `plan`) VALUES (1, '默认租户', 'enterprise')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
