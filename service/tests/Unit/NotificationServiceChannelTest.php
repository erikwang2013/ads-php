<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * NotificationService 渠道分发测试：
 *   - 探针子类记录各渠道调用（不触碰 DB / Redis / 网络）
 *   - 真实渠道的优雅降级路径（缺配置时不抛异常）
 *   - Email 文案复用与 Webhook 载荷结构断言
 */

namespace Tests\Unit;

use plugin\ads_alert\model\AlertLog;
use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\service\NotificationService;
use plugin\ads_alert\service\channel\EmailChannel;
use plugin\ads_alert\service\channel\WebhookChannel;
use PHPUnit\Framework\TestCase;

/**
 * 渠道分发探针：重写各渠道方法仅记录调用，隔离 DB/Redis/网络副作用。
 */
class SpyNotificationService extends NotificationService
{
    /** @var string[] */
    public array $dispatched = [];

    protected function sendWeb(AlertLog $log, AlertRule $rule): void
    {
        $this->dispatched[] = 'web';
    }

    protected function sendEmail(AlertLog $log, AlertRule $rule): void
    {
        $this->dispatched[] = 'email';
    }

    protected function sendSms(AlertLog $log, AlertRule $rule): void
    {
        $this->dispatched[] = 'sms';
    }

    protected function sendWebhook(AlertLog $log, AlertRule $rule): void
    {
        $this->dispatched[] = 'webhook';
    }

    protected function publishToRedis(AlertLog $log, AlertRule $rule): void
    {
        $this->dispatched[] = 'redis';
    }
}

class NotificationServiceChannelTest extends TestCase
{
    protected function makeRule(?array $channels = null, string $webhookUrl = 'https://example.com/hook'): AlertRule
    {
        $rule = new AlertRule();
        $rule->forceFill([
            'id'          => 1,
            'tenant_id'   => 1,
            'name'        => '测试规则',
            'metric'      => 'cost',
            'condition'   => 'gt',
            'threshold'   => 100.0,
            'channels'    => $channels,
            'webhook_url' => $webhookUrl,
            'enabled'     => true,
        ]);
        return $rule;
    }

    protected function makeLog(): AlertLog
    {
        $log = new AlertLog();
        $log->forceFill([
            'id'            => 1,
            'tenant_id'     => 1,
            'rule_id'       => 1,
            'rule_name'     => '测试规则',
            'metric'        => 'cost',
            'current_value' => 120.5,
            'threshold'     => 100.0,
            'condition'     => 'gt',
            'status'        => 'triggered',
            'created_at'    => '2026-08-16 10:00:00',
        ]);
        return $log;
    }

    /**
     * 将 error_log 重定向到临时文件执行回调，返回捕获的日志内容。
     */
    protected function captureErrorLog(callable $fn): string
    {
        $file = tempnam(sys_get_temp_dir(), 'alert_log_');
        $old = ini_set('error_log', $file);
        try {
            $fn();
        } finally {
            ini_set('error_log', $old);
        }
        $content = (string) file_get_contents($file);
        @unlink($file);
        return $content;
    }

    public function testDispatchWebOnly(): void
    {
        $service = new SpyNotificationService();
        $service->send($this->makeLog(), $this->makeRule(['web']));

        $this->assertSame(['web', 'redis'], $service->dispatched);
    }

    public function testDispatchAllChannels(): void
    {
        $service = new SpyNotificationService();
        $service->send($this->makeLog(), $this->makeRule(['web', 'email', 'sms', 'webhook']));

        $this->assertSame(['web', 'email', 'sms', 'webhook', 'redis'], $service->dispatched);
    }

    public function testUnknownChannelIsIgnored(): void
    {
        $service = new SpyNotificationService();
        $service->send($this->makeLog(), $this->makeRule(['pagerduty']));

        $this->assertSame(['redis'], $service->dispatched);
    }

    public function testDefaultChannelsIsWebWhenChannelsMissing(): void
    {
        $service = new SpyNotificationService();
        $service->send($this->makeLog(), $this->makeRule(null));

        $this->assertSame(['web', 'redis'], $service->dispatched);
    }

    public function testSendWithRealWebChannelDoesNotThrow(): void
    {
        // 真实 NotificationService：web 渠道走 ads_notifications insert 路径，
        // Redis pub/sub 走 alert:new 推送；两者内部失败均被捕获，send() 不抛异常。
        $service = new NotificationService();
        $service->send($this->makeLog(), $this->makeRule(['web']));

        $this->addToAssertionCount(1);
    }

    public function testEmailChannelGracefulDegradeWhenNotConfigured(): void
    {
        $channel = new EmailChannel();
        $log = $this->captureErrorLog(function () use ($channel) {
            $channel->send($this->makeLog(), $this->makeRule(['email']));
        });

        $this->assertStringContainsString('SMTP 未配置', $log);
    }

    public function testEmailMessageReusesSendWebTemplate(): void
    {
        $reflection = new \ReflectionClass(EmailChannel::class);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        [$subject, $body] = $method->invoke(new EmailChannel(), $this->makeLog(), $this->makeRule(['email']));

        $this->assertSame('告警触发: 测试规则', $subject);
        // 与 sendWeb 文案模板一致：指标/当前值/条件/阈值
        $this->assertStringContainsString('指标 cost 当前值 120.5 gt 阈值 100', $body);
    }

    public function testWebhookChannelSkipsEmptyUrl(): void
    {
        $channel = new WebhookChannel();
        $log = $this->captureErrorLog(function () use ($channel) {
            $channel->send($this->makeLog(), $this->makeRule(['webhook'], ''));
        });

        $this->assertStringContainsString('未配置 webhook_url', $log);
    }

    public function testWebhookChannelRejectsNonHttpUrl(): void
    {
        $channel = new WebhookChannel();
        $log = $this->captureErrorLog(function () use ($channel) {
            $channel->send($this->makeLog(), $this->makeRule(['webhook'], 'ftp://example.com/hook'));
        });

        $this->assertStringContainsString('仅支持 http/https', $log);
    }

    public function testWebhookPayloadStructure(): void
    {
        $reflection = new \ReflectionClass(WebhookChannel::class);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);

        $payload = $method->invoke(new WebhookChannel(), $this->makeLog(), $this->makeRule(['webhook']));

        $this->assertSame('alert.triggered', $payload['event']);
        $this->assertSame(1, $payload['alert']['id']);
        $this->assertSame(1, $payload['alert']['tenant_id']);
        $this->assertSame(1, $payload['alert']['rule_id']);
        $this->assertSame('cost', $payload['alert']['metric']);
        $this->assertSame(120.5, $payload['alert']['current_value']);
        $this->assertSame(100.0, $payload['alert']['threshold']);
        $this->assertSame('gt', $payload['alert']['condition']);
        $this->assertSame('triggered', $payload['alert']['status']);
        $this->assertSame('2026-08-16 10:00:00', $payload['alert']['created_at']);
        $this->assertSame('测试规则', $payload['rule']['name']);
        $this->assertSame('https://example.com/hook', $payload['rule']['webhook_url']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $payload['timestamp']);
    }
}
