-- Incremental migration: add webhook_url column to erik_alert_rules
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 说明：
--   1. create_alerts.sql 是全新安装脚本（CREATE TABLE IF NOT EXISTS），
--      对已存在的表不会做任何变更，因此需要本增量脚本为已有环境补列。
--   2. 本脚本需要手动执行（phpMyAdmin / mysql CLI）：
--        mysql -u root -p ads < service/plugin/ads-alert/migration/create_alert_webhook_url.sql
--   3. 幂等性：MySQL 8.0 不支持 ADD COLUMN IF NOT EXISTS，重复执行会报
--      "Duplicate column name 'webhook_url'"。执行前可先检查列是否存在：
--        SELECT COLUMN_NAME FROM information_schema.COLUMNS
--          WHERE TABLE_SCHEMA='ads' AND TABLE_NAME='erik_alert_rules'
--            AND COLUMN_NAME='webhook_url';
--   4. install.sql（整体安装入口）中 erik_alert_rules 的表定义已同步补充该列，
--      全新安装直接建表即包含 webhook_url，无需再执行本脚本。

ALTER TABLE `erik_alert_rules`
    ADD COLUMN `webhook_url` VARCHAR(512) NULL COMMENT 'Webhook 回调地址（webhook 渠道用）' AFTER `channels`;
