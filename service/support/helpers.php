<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Global helper functions loaded via config/autoload.php.
 */

if (!function_exists('redis')) {
    /**
     * Get the shared Redis client (config/redis.php default connection).
     */
    function redis(): \Redis
    {
        static $client = null;

        if ($client instanceof \Redis && $client->ping() !== false) {
            return $client;
        }

        $config = config('redis.default');
        $client = new \Redis();
        $client->connect(
            $config['host'] ?? '127.0.0.1',
            (int) ($config['port'] ?? 6379),
            (float) ($config['connection_timeout'] ?? 3)
        );
        if (!empty($config['password'])) {
            $client->auth($config['password']);
        }
        $client->select((int) ($config['database'] ?? 0));
        return $client;
    }
}

if (!function_exists('now')) {
    /**
     * Get the current time (Laravel-compatible global helper).
     *
     * 项目多处（控制器/定时任务/引擎）调用全局 now()，但 vendor 中不存在
     * 该全局函数（illuminate 内仅有类方法），导致这些写入路径运行时抛出
     * "Call to undefined function now()" 并被 catch 吞掉（静默失败）。
     * 此处补定义以恢复全部 45 处调用：返回 Carbon 实例，Eloquent 写入时
     * 自动格式化为 'Y-m-d H:i:s'，字符串上下文亦可隐式转换。
     */
    function now(): \Carbon\CarbonInterface
    {
        return \Carbon\Carbon::now();
    }
}

if (!function_exists('snowflake_id')) {
    /**
     * 生成 snowflake BIGINT 主键。
     * 被 plugin/ads-report/service/AttributionEngine.php 与
     * plugin/ads-task/task/BudgetCheckTask.php 调用但从未定义，
     * 相关写入路径（归因计算/预算检查）会抛 "Call to undefined function"。
     * 与 SnowflakeTrait 使用同一生成器。
     */
    function snowflake_id(): int
    {
        static $generator = null;
        if ($generator === null) {
            $generator = new \Erikwang2013\Snowflake\Snowflake();
        }
        return $generator->id();
    }
}
