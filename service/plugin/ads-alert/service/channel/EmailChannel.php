<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * EmailChannel — 告警邮件发送（SMTP，PHP 原生实现，零 composer 依赖）
 *
 * 实现方式决策（记录理由）：
 *   不引入 PHPMailer / symfony/mailer：
 *   1) vendor/ 中不存在任何现成邮件库，引入需 composer require（网络 + composer.lock 变更）；
 *   2) 需求仅为"简单文本邮件 + SMTP 认证"，PHP 原生 stream_socket_client 足以覆盖；
 *   3) 符合 Phase 8 规划"优先考虑不引依赖的 mail()/socket 实现以保持轻量"。
 *   原生实现能力：
 *   - ssl 直连（465）与 STARTTLS（587）
 *   - AUTH LOGIN 认证（username 为空时跳过）
 *   - mail() 函数作为 SMTP 失败后的最后降级通道
 *
 * 收件人来源（按优先级，见 resolveRecipients）：
 *   1. AlertRule 未来新增 recipients 字段（本期表结构无此字段）；
 *   2. 租户级邮件配置（未来）；
 *   3. config('mail.to')，即环境变量 MAIL_TO —— 当前唯一生效来源。
 *
 * 失败处理：所有异常/错误在此捕获并 error_log，绝不向上抛出（不中断主流程与其他渠道）。
 */

namespace plugin\ads_alert\service\channel;

use plugin\ads_alert\model\AlertLog;
use plugin\ads_alert\model\AlertRule;
use RuntimeException;
use Throwable;

class EmailChannel
{
    /**
     * 发送告警邮件。任何失败仅记录日志，不抛异常。
     */
    public function send(AlertLog $log, AlertRule $rule): void
    {
        try {
            $config = (array) config('mail', []);

            if (empty($config['host']) || empty($config['from'])) {
                error_log('[alert email] SMTP 未配置（MAIL_HOST / MAIL_FROM 缺失），跳过邮件发送');
                return;
            }

            $to = $this->resolveRecipients($rule, $config);
            if ($to === '') {
                error_log('[alert email] 无可用收件人（AlertRule 无收件人字段且未配置 MAIL_TO），跳过邮件发送');
                return;
            }

            [$subject, $body] = $this->buildMessage($log, $rule);

            if (!$this->sendViaSmtp($config, $to, $subject, $body)) {
                $this->sendViaMailFallback($to, $subject, $body);
            }
        } catch (Throwable $e) {
            error_log('[alert email] 发送失败: ' . $e->getMessage());
        }
    }

    /**
     * 收件人解析。
     * 优先级：AlertRule.recipients（预留）> 租户配置（预留）> config('mail.to')（MAIL_TO）。
     */
    protected function resolveRecipients(AlertRule $rule, array $config): string
    {
        $to = $rule->recipients ?? ($config['to'] ?? '');
        return is_string($to) ? trim($to) : '';
    }

    /**
     * 组装邮件主题与正文（正文复用 sendWeb 的告警文案模板）。
     *
     * @return array{0: string, 1: string} [subject, body]
     */
    protected function buildMessage(AlertLog $log, AlertRule $rule): array
    {
        $subject = "告警触发: {$rule->name}";
        $body = implode("\n", [
            "告警触发: {$rule->name}",
            "指标 {$log->metric} 当前值 {$log->current_value} {$log->condition} 阈值 {$log->threshold}",
            "状态: {$log->status}",
            '时间: ' . date('Y-m-d H:i:s'),
        ]);
        return [$subject, $body];
    }

    /**
     * 通过原生 SMTP 发送。成功返回 true；失败记录日志并返回 false（由上层决定是否走 mail() 降级）。
     */
    protected function sendViaSmtp(array $config, string $to, string $subject, string $body): bool
    {
        $host = (string) $config['host'];
        $port = (int) ($config['port'] ?? 465);
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $from = (string) $config['from'];
        $fromName = (string) ($config['from_name'] ?? 'Ads Alert');
        $encryption = strtolower((string) ($config['encryption'] ?? 'ssl'));
        $timeout = (int) ($config['timeout'] ?? 10);
        $helo = $config['helo'] ?? 'localhost';

        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "{$scheme}://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );
        if (!$socket) {
            error_log("[alert email] SMTP 连接失败 {$host}:{$port} (errno={$errno}): {$errstr}");
            return false;
        }
        stream_set_timeout($socket, $timeout);

        try {
            $this->expect($socket, '220');

            $this->command($socket, "EHLO {$helo}", '250');

            // STARTTLS：在认证前将明文连接升级为 TLS
            if ($encryption === 'tls' || $encryption === 'starttls') {
                $this->command($socket, 'STARTTLS', '220');
                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$crypto) {
                    error_log('[alert email] STARTTLS 加密协商失败');
                    return false;
                }
                $this->command($socket, "EHLO {$helo}", '250');
            }

            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', '334');
                $this->command($socket, base64_encode($username), '334');
                $this->command($socket, base64_encode($password), '235');
            }

            $this->command($socket, "MAIL FROM:<{$from}>", '250');
            foreach (array_map('trim', explode(',', $to)) as $recipient) {
                if ($recipient !== '') {
                    $this->command($socket, "RCPT TO:<{$recipient}>", '250');
                }
            }

            $this->command($socket, 'DATA', '354');
            $headers = [
                'From: ' . ($fromName !== '' ? $this->encodeHeader($fromName) . " <{$from}>" : $from),
                'To: ' . $to,
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'Date: ' . date('r'),
                '',
            ];
            $message = implode("\r\n", $headers) . "\r\n" . $this->sanitizeBody($body) . "\r\n.\r\n";
            fwrite($socket, $message);
            $this->expect($socket, '250');

            $this->command($socket, 'QUIT', '221');
            return true;
        } catch (Throwable $e) {
            error_log('[alert email] SMTP 发送失败: ' . $e->getMessage());
            return false;
        } finally {
            fclose($socket);
        }
    }

    /**
     * mail() 降级通道：SMTP 失败时尝试系统 sendmail。失败仅记日志。
     */
    protected function sendViaMailFallback(string $to, string $subject, string $body): void
    {
        if (!function_exists('mail')) {
            error_log('[alert email] mail() 不可用，且 SMTP 发送失败，邮件未发送');
            return;
        }
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ]);
        $sent = @mail($to, $this->encodeHeader($subject), $body, $headers);
        if (!$sent) {
            error_log('[alert email] mail() 降级发送失败');
        }
    }

    /**
     * 发送单条 SMTP 命令并校验期望响应码（如 250/334/354/235）。
     */
    protected function command($socket, string $command, string $expected): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    /**
     * 读取 SMTP 响应并断言以期望码开头，否则抛出异常。
     */
    protected function expect($socket, string $expected): void
    {
        $response = $this->readResponse($socket);
        if (!str_starts_with($response, $expected)) {
            throw new RuntimeException('SMTP 期望响应码 ' . $expected . '，实际: ' . trim($response));
        }
    }

    /**
     * 读取完整响应（处理多行响应，以第 4 个字符是否为 '-' 判断是否续行）。
     */
    protected function readResponse($socket): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] !== '-') {
                break;
            }
        }
        if ($response === '') {
            throw new RuntimeException('SMTP 无响应（连接已关闭或超时）');
        }
        return $response;
    }

    /**
     * SMTP DATA 正文规范化：统一 CRLF，并对以 "." 开头的行做透明性转义。
     */
    protected function sanitizeBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $body);
        foreach ($lines as &$line) {
            if (str_starts_with($line, '.')) {
                $line = '.' . $line;
            }
        }
        return implode("\r\n", $lines);
    }

    /**
     * RFC 2047 编码：含非 ASCII 字符时用 UTF-8 Base64 编码邮件头。
     */
    protected function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}
