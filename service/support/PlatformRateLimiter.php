<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace erik\support;

class PlatformRateLimiter
{
    protected static array $limits = [
        'juliang'     => 10,  // 巨量引擎 10 QPS
        'baidu'       => 5,   // 百度 5 QPS
        'taobao'      => 10,  // 淘宝 10 QPS
        'tencent'     => 20,  // 腾讯 20 QPS
        'kuaishou'    => 10,  // 快手 10 QPS
        'google'      => 5,   // Google Ads 5 QPS (strict)
        'meta'        => 5,   // Meta 5 QPS
        'tiktok'      => 10,  // TikTok 10 QPS
        'default'     => 5,
    ];

    public static function acquire(string $platform): bool
    {
        $redis = redis();
        if (!$redis) return true;

        $qps = static::$limits[$platform] ?? static::$limits['default'];
        $key = "rate:platform:{$platform}:" . time();
        $current = $redis->incr($key);
        if ($current === 1) $redis->expire($key, 1);
        return $current <= $qps;
    }

    public static function wait(string $platform): void
    {
        while (!static::acquire($platform)) {
            usleep(100000); // 100ms
        }
    }
}
