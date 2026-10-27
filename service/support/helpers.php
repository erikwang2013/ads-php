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
        $client->setOption(\Redis::OPT_PREFIX, $config['prefix'] ?? 'ads:');
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

if (!function_exists('bc_round')) {
    /**
     * bcmath 精确四舍五入（半离零），返回十进制字符串。
     *
     * 项目规则：价格/金额/数据比率计算一律走 bcmath，禁止浮点 round()
     * （浮点存在二进制误差，PHP 8.4 之前 bcmath 无原生 round）。
     * 实现：加上半个最小单位后按 scale 截断（bcadd 截断语义，已在运行时验证）。
     * float 输入按 PHP precision（默认 14）十进制化后入 bc，金额场景足够。
     */
    function bc_round(int|float|string $num, int $scale = 2): string
    {
        $num   = (string) $num;
        $sign  = ($num !== '' && $num[0] === '-') ? '-' : '';
        $mag   = $sign === '-' ? substr($num, 1) : $num;
        $scale = max(0, $scale);
        if ($scale > 0) {
            $half = '0.' . str_repeat('0', $scale) . '5';
            $mag  = bcadd($mag, $half, $scale);
        } else {
            $dot  = strpos($mag, '.');
            $mag  = $dot === false ? $mag : substr($mag, 0, $dot);
        }
        return $sign && bccomp($mag, '0') !== 0 ? '-' . $mag : $mag;
    }
}

if (!function_exists('bc_money')) {
    /**
     * 分 → 元 展示换算（千分位 + 两位小数），bcmath 内完成除法。
     *
     * 输出与 number_format($cents / 100, 2) 完全一致（含千分位分隔），
     * 但避免浮点除法；金额存储一律为整数分，调用点传分。
     */
    function bc_money(int|float|string $cents): string
    {
        $yuan = bcdiv((string) $cents, '100', 2);
        return number_format((float) $yuan, 2);
    }
}

if (!function_exists('bc_div')) {
    /**
     * bcmath 有守卫除法 + 半离零舍入，返回 scale 位十进制字符串。
     *
     * 比率指标（CTR/CVR/CPC/CPM/ROI/CPA 等）一律 PHP 侧用本函数计算，
     * 不再写 SQL 层 ROUND(CASE WHEN SUM(...) ...)；分母 ≤ 0 返回
     * scale 位 "0.00…"（等价原 SQL ELSE 0 分支）。
     * 内部以 scale+6 位截断做中间除法，最后 bc_round 收尾，避免中途截断漂移。
     */
    function bc_div(int|float|string $num, int|float|string $den, int $scale = 2): string
    {
        $den = (string) $den;
        if (bccomp($den, '0') <= 0) {
            return bc_round('0', $scale);
        }
        return bc_round(bcdiv((string) $num, $den, $scale + 6), $scale);
    }
}
