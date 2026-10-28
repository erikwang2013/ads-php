<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

/**
 * 多框架共用的插件布局主配置：顶层 {@code key} / {@code cipher}。
 *
 * - Webman：置于 {@code config/plugin/{vendor}/{package}/app.php}，读取 {@code config('plugin.{vendor}.{package}.app.*')}。
 * - Laravel / Lumen / ThinkPHP：同路径，由本包在启动时合并或注入到 {@code encryptable.*}。
 *
 * 升级兼容（v4.0.17 及更早版本）：旧安装只配置了 APP_ENCRYPTION_KEY，
 * 且字段用 aes-128-ecb 加密。升级时须把 APP_ENCRYPTION_KEY 的值复制到
 * ENCRYPTION_KEY，并在所有字段用 aes-256-gcm 重新加密前保留
 * ENCRYPTION_CIPHER=aes-128-ecb。
 *
 * @see https://webman.workerman.net/doc/en/plugin/create.html
 */
return [
    // 与 EnvEncryptableConfig 保持一致：字段加密读 ENCRYPTION_KEY / ENCRYPTION_CIPHER
    'key' => env('ENCRYPTION_KEY') ?: env('APP_ENCRYPTION_KEY'),
    'cipher' => env('ENCRYPTION_CIPHER', 'aes-256-gcm'),
    'previous_keys' => \Erikwang2013\Encryptable\Support\PreviousKeysParser::parse(env('ENCRYPTION_PREVIOUS_KEYS')),
];
