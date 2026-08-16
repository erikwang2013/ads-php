<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 邮件（SMTP）配置 — 全部由环境变量驱动（MAIL_* 前缀）
 *
 * 用途：告警通知邮件发送（plugin/ads-alert 的 EmailChannel）。
 * 未配置（MAIL_HOST/MAIL_FROM 为空）时 EmailChannel 优雅降级：仅记录日志，不中断主流程。
 *
 * .env 配置示例：
 *   MAIL_HOST=smtp.example.com     # SMTP 服务器
 *   MAIL_PORT=465                  # 465=ssl / 587=tls(STARTTLS) / 25=明文
 *   MAIL_USERNAME=alert@example.com
 *   MAIL_PASSWORD=your-password
 *   MAIL_FROM=alert@example.com    # 发件人地址
 *   MAIL_FROM_NAME=Ads Alert       # 发件人名称
 *   MAIL_ENCRYPTION=ssl            # ssl | tls | none
 *   MAIL_TO=ops@example.com        # 默认收件人（AlertRule 无收件人字段时使用）
 */

return [
    // SMTP 服务器地址（为空则跳过邮件发送）
    'host' => env('MAIL_HOST', ''),

    // SMTP 端口：465(ssl) / 587(tls) / 25(明文)
    'port' => (int) env('MAIL_PORT', 465),

    // SMTP 认证用户名（留空则跳过 AUTH，适用于无需认证的本地中继）
    'username' => env('MAIL_USERNAME', ''),

    // SMTP 认证密码
    'password' => env('MAIL_PASSWORD', ''),

    // 发件人地址
    'from' => env('MAIL_FROM', ''),

    // 发件人名称（用于 From 头）
    'from_name' => env('MAIL_FROM_NAME', 'Ads Alert'),

    // 加密方式：ssl（465 直连加密）/ tls（587 STARTTLS 升级）/ none（明文）
    'encryption' => env('MAIL_ENCRYPTION', 'ssl'),

    // 默认收件人（逗号分隔多个地址）。
    // AlertRule 当前无收件人字段，邮件收件人统一从此处读取。
    'to' => env('MAIL_TO', ''),

    // 连接与读写超时（秒）
    'timeout' => (int) env('MAIL_TIMEOUT', 10),
];
