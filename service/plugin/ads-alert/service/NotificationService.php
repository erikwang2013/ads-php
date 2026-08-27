<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_alert\service;

use plugin\ads_alert\model\AlertLog;
use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\service\channel\EmailChannel;
use plugin\ads_alert\service\channel\WebhookChannel;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class NotificationService
{
    /**
     * Send notification for a triggered alert through configured channels.
     */
    public function send(AlertLog $log, AlertRule $rule): void
    {
        $channels = $rule->channels ?? ['web'];

        foreach ($channels as $channel) {
            match ($channel) {
                'web'     => $this->sendWeb($log, $rule),
                'email'   => $this->sendEmail($log, $rule),
                'sms'     => $this->sendSms($log, $rule),
                'webhook' => $this->sendWebhook($log, $rule),
                default   => null,
            };
        }

        // Publish to Redis pub/sub for real-time push
        $this->publishToRedis($log, $rule);
    }

    /**
     * Web channel: store a notification record for polling.
     */
    protected function sendWeb(AlertLog $log, AlertRule $rule): void
    {
        try {
            DB::table('ads_notifications')->insert([
                'tenant_id'  => $log->tenant_id,
                'type'       => 'alert',
                'title'      => "告警触发: {$rule->name}",
                'content'    => "指标 {$log->metric} 当前值 {$log->current_value} {$log->condition} 阈值 {$log->threshold}",
                'ref_type'   => 'alert_log',
                'ref_id'     => $log->id,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // notifications table may not exist yet — silently skip
            echo "Web notification insert failed: {$e->getMessage()}\n";
        }
    }

    /**
     * Email channel: send via EmailChannel (SMTP + mail() fallback).
     */
    protected function sendEmail(AlertLog $log, AlertRule $rule): void
    {
        (new EmailChannel())->send($log, $rule);
    }

    /**
     * Webhook channel: POST JSON payload to $rule->webhook_url.
     */
    protected function sendWebhook(AlertLog $log, AlertRule $rule): void
    {
        (new WebhookChannel())->send($log, $rule);
    }

    /**
     * SMS channel (placeholder).
     *
     * 接入步骤（后续阶段）：
     *   1. 在 config/ 新增 sms.php，配置短信网关（阿里云 dysmsapi / 腾讯云 sms）：
     *      access_key_id / access_key_secret（AK/SK）、sign_name（签名）、template_code（模板）。
     *   2. 新建 service/channel/SmsChannel.php，实现 send(AlertLog, AlertRule)：
     *      调用网关 API（如阿里云 SendSms），将 rule.name / metric / current_value 填入模板变量。
     *   3. 在本方法中替换为 (new SmsChannel())->send($log, $rule)。
     *
     * 注意：短信网关为付费服务且需 AK/SK，本阶段仅保留占位并明确记录"未配置"。
     */
    protected function sendSms(AlertLog $log, AlertRule $rule): void
    {
        error_log("[alert sms] 短信网关未配置（需阿里云/腾讯云 AK/SK），跳过：Alert '{$rule->name}' triggered: {$log->metric} = {$log->current_value}");
    }

    /**
     * Publish alert event to Redis pub/sub channel.
     */
    protected function publishToRedis(AlertLog $log, AlertRule $rule): void
    {
        try {
            $redis = redis();
            $payload = json_encode([
                'event'        => 'alert.triggered',
                'log_id'       => $log->id,
                'rule_id'      => $rule->id,
                'rule_name'    => $rule->name,
                'metric'       => $log->metric,
                'current_value' => $log->current_value,
                'threshold'    => $log->threshold,
                'condition'    => $log->condition,
                'tenant_id'    => $log->tenant_id,
                'timestamp'    => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE);
            $redis->publish('alert:new', $payload);
        } catch (Throwable $e) {
            // Redis might not be configured — silently skip
            echo "Redis publish failed: {$e->getMessage()}\n";
        }
    }
}
