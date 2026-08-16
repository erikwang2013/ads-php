-- Phase 10 Task 2: erik_conversions 增加 campaign_id / channel / updated_at
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 用途：为已存在的数据库补齐转化回传 API 所需字段（install.sql 与
-- create_conversions.sql 已同步更新，全新安装无需执行本文件）。
--
-- 注意：MySQL 8.0 不支持 ADD COLUMN IF NOT EXISTS，本文件对同一库只能执行一次；
-- 若个别列已存在（如旧环境已手工加过 campaign_id/channel），请先注释掉对应行；
-- 若 uk_order 唯一键已存在（曾执行过本文件或手工建过），请注释第 14 行 ADD UNIQUE KEY。
ALTER TABLE `erik_conversions`
    ADD COLUMN `campaign_id` BIGINT UNSIGNED NULL COMMENT '关联 erik_campaigns.id' AFTER `platform`,
    ADD COLUMN `channel` VARCHAR(32) DEFAULT 'api' COMMENT '转化来源渠道' AFTER `currency`,
    ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
    ADD INDEX `idx_campaign` (`campaign_id`),
    ADD UNIQUE KEY `uk_order` (`tenant_id`, `platform`, `order_id`);
