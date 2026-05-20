-- Performance indexes for ad platform tables
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- erik_campaigns: optimize platform-filtered queries
ALTER TABLE `erik_campaigns` ADD INDEX `idx_tenant_platform` (`tenant_id`, `platform`);

-- erik_ad_groups: optimize campaign JOIN + status filter
ALTER TABLE `erik_ad_groups` ADD INDEX `idx_campaign_status` (`campaign_id`, `status`);

-- erik_creatives: optimize ad_group JOIN + media_type filter
ALTER TABLE `erik_creatives` ADD INDEX `idx_adgroup_media` (`ad_group_id`, `media_type`);

-- erik_report_metrics: optimize dashboard summary queries
ALTER TABLE `erik_report_metrics` ADD INDEX `idx_tenant_date` (`tenant_id`, `date`);

-- erik_alert_rules: optimize platform-filtered queries
ALTER TABLE `erik_alert_rules` ADD INDEX `idx_tenant_platform` (`tenant_id`, `platform`);

-- erik_alert_logs: optimize rule-filtered queries
ALTER TABLE `erik_alert_logs` ADD INDEX `idx_tenant_rule` (`tenant_id`, `rule_id`);
