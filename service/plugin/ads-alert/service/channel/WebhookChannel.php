<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * WebhookChannel — 通用 Webhook 告警通知（POST JSON）
 *
 * 目标地址：AlertRule.webhook_url（migration 新增列，见
 *   plugin/ads-alert/migration/create_alert_webhook_url.sql）。
 *
 * 载荷格式：
 *   {
 *     event: 'alert.triggered',
 *     alert: {id, tenant_id, rule_id, metric, current_value, threshold, condition, status, created_at},
 *     rule:  {name, webhook_url},
 *     timestamp: 'Y-m-d H:i:s'
 *   }
 *
 * 超时与重试：连接超时 5s、总超时 10s；失败仅 error_log，不重试（保持简单）。
 *
 * 安全：
 *   - 仅允许 http/https 协议；
 *   - 不跟随重定向（CURLOPT_FOLLOWLOCATION=false）；
 *   - 已知限制：未对内网地址（127.0.0.1 / 10.x / 192.168.x / 169.254.x 等）做校验，
 *     存在 SSRF 风险。生产环境如需加固，可在 send() 中解析主机并拦截内网/保留地址，
 *     本期为保持实现简单，仅记录该限制。
 */

namespace plugin\ads_alert\service\channel;

use plugin\ads_alert\model\AlertLog;
use plugin\ads_alert\model\AlertRule;
use Throwable;

class WebhookChannel
{
    /** 连接超时（秒） */
    protected const CONNECT_TIMEOUT = 5;

    /** 总超时（秒） */
    protected const TOTAL_TIMEOUT = 10;

    /**
     * POST JSON 载荷到规则配置的 webhook_url。任何失败仅记录日志，不抛异常。
     */
    public function send(AlertLog $log, AlertRule $rule): void
    {
        try {
            $url = is_string($rule->webhook_url) ? trim($rule->webhook_url) : '';
            if ($url === '') {
                error_log('[alert webhook] 规则未配置 webhook_url，跳过');
                return;
            }
            if (!preg_match('#^https?://#i', $url)) {
                error_log("[alert webhook] webhook_url 仅支持 http/https: {$url}");
                return;
            }

            $payload = json_encode($this->buildPayload($log, $rule), JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                error_log('[alert webhook] 载荷 JSON 编码失败');
                return;
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Content-Length: ' . strlen($payload)],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => self::TOTAL_TIMEOUT,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno !== 0) {
                error_log("[alert webhook] 请求失败 (errno={$errno}): {$error}, url={$url}");
                return;
            }
            if ($httpCode < 200 || $httpCode >= 300) {
                error_log("[alert webhook] 非 2xx 响应: HTTP {$httpCode}, url={$url}");
            }
        } catch (Throwable $e) {
            error_log('[alert webhook] 发送失败: ' . $e->getMessage());
        }
    }

    /**
     * 组装 webhook 载荷（独立方法便于单元测试断言字段完整性）。
     */
    protected function buildPayload(AlertLog $log, AlertRule $rule): array
    {
        return [
            'event' => 'alert.triggered',
            'alert' => [
                'id'            => $log->id,
                'tenant_id'     => $log->tenant_id,
                'rule_id'       => $log->rule_id,
                'metric'        => $log->metric,
                'current_value' => $log->current_value,
                'threshold'     => $log->threshold,
                'condition'     => $log->condition,
                'status'        => $log->status,
                'created_at'    => $log->created_at ? (string) $log->created_at : date('Y-m-d H:i:s'),
            ],
            'rule' => [
                'name'        => $rule->name,
                'webhook_url' => $rule->webhook_url,
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
