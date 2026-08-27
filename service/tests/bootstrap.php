<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Set up minimal environment for tests
putenv('APP_DEBUG=true');
putenv('JWT_SECRET=test-secret');
putenv('HASHIDS_SALT=test-salt');
putenv('DB_HOST=127.0.0.1');
putenv('DB_DATABASE=ads_test');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=' . (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ''));

// 测试专用 redis() 桩：返回 null 模拟 Redis 不可用，驱动限流/防重放等中间件
// 走优雅降级路径（不连接真实 Redis）。须在 require helpers.php 之前定义，
// 生产实现见 support/helpers.php（function_exists 守卫不会覆盖本桩）。
if (!function_exists('redis')) {
    function redis(): ?\Redis
    {
        return null;
    }
}

// Encryptable 模型字段 cast（erikwang2013/encryptable）需要加密密钥
putenv('ENCRYPTION_KEY=' . str_repeat('k', 32));

// Global helpers (now()/redis() 等) 与生产环境保持一致
require_once __DIR__ . '/../support/helpers.php';

// 加载 log 配置（tests/config/log.php 复用生产 config/log.php），
// 使被捕获异常的 logError() 路径（support\Log::channel）在测试中可用。
\Webman\Config::load(__DIR__ . '/config');

// Initialize database capsule for tests
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => env('DB_HOST', '127.0.0.1'),
    'database'  => env('DB_DATABASE', 'ads_test'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
], 'default');
$capsule->setAsGlobal();

// Eloquent 事件分发器：SnowflakeTrait / 各模型 creating 钩子（id/created_at 生成）
// 依赖事件分发；未设置时模型 create() 会因无默认 id 而报 1364。
$capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));

$capsule->bootEloquent();
