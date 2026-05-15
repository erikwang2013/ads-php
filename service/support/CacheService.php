<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace erik\support;

class CacheService
{
    protected static int $defaultTtl = 300; // 5 minutes

    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $redis = redis();
        if (!$redis) return $callback();

        $cached = $redis->get($key);
        if ($cached !== false) {
            return json_decode($cached, true);
        }

        $value = $callback();
        $redis->setex($key, $ttl ?? static::$defaultTtl, json_encode($value, JSON_UNESCAPED_UNICODE));
        return $value;
    }

    public static function forget(string $key): void
    {
        $redis = redis();
        if ($redis) $redis->del($key);
    }

    public static function flush(string $prefix = 'cache:'): void
    {
        $redis = redis();
        if (!$redis) return;
        $keys = $redis->keys($prefix . '*');
        foreach ($keys as $key) {
            $redis->del($key);
        }
    }

    public static function dashboardKey(int $tenantId, string $dateStart, string $dateEnd): string
    {
        return "cache:dashboard:{$tenantId}:{$dateStart}:{$dateEnd}";
    }

    public static function campaignListKey(int $tenantId, array $filters, int $page): string
    {
        return 'cache:campaigns:' . $tenantId . ':' . md5(json_encode($filters)) . ':' . $page;
    }
}
