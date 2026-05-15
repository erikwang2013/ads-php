<?php
namespace plugin\ads_alert\service;

use plugin\ads_alert\model\AlertLog;
use plugin\ads_alert\model\AlertRule;
use Illuminate\Database\Capsule\Manager as DB;

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
                'web'   => $this->sendWeb($log, $rule),
                'email' => $this->sendEmail($log, $rule),
                'sms'   => $this->sendSms($log, $rule),
                default => null,
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
            DB::table('notifications')->insert([
                'tenant_id'  => $log->tenant_id,
                'type'       => 'alert',
                'title'      => "告警触发: {$rule->name}",
                'content'    => "指标 {$log->metric} 当前值 {$log->current_value} {$log->condition} 阈值 {$log->threshold}",
                'ref_type'   => 'alert_log',
                'ref_id'     => $log->id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // notifications table may not exist yet — silently skip
            echo "Web notification insert failed: {$e->getMessage()}\n";
        }
    }

    /**
     * Email channel (stub — to be implemented with mailer integration).
     */
    protected function sendEmail(AlertLog $log, AlertRule $rule): void
    {
        // Stub: integrate with mail service in a future phase
        echo "[email stub] Alert '{$rule->name}' triggered: {$log->metric} = {$log->current_value}\n";
    }

    /**
     * SMS channel (stub — to be implemented with SMS provider integration).
     */
    protected function sendSms(AlertLog $log, AlertRule $rule): void
    {
        // Stub: integrate with SMS provider in a future phase
        echo "[sms stub] Alert '{$rule->name}' triggered: {$log->metric} = {$log->current_value}\n";
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
                'timestamp'    => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE);
            $redis->publish('alert:new', $payload);
        } catch (\Throwable $e) {
            // Redis might not be configured — silently skip
            echo "Redis publish failed: {$e->getMessage()}\n";
        }
    }
}
