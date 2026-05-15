CREATE TABLE IF NOT EXISTS `erik_sync_errors` (
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
