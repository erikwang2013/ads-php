<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CacheService — two-level cache: L1 (APCu memory) + L2 (Redis).
 *
 * L1 (APCu): per-process memory, sub-millisecond access. Best for hot keys.
 * L2 (Redis): shared across workers, persistent. Falls through on miss.
 */

namespace erik\support;

class CacheService
{
    protected static int $defaultTtl = 300;
    protected static array $l1Cache = [];

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (isset(self::$l1Cache[$key])) {
            return self::$l1Cache[$key];
        }

        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($key, $hit);
            if ($hit) {
                self::$l1Cache[$key] = $cached;
                return $cached;
            }
        }

        try {
            $redis = redis();
            $cached = $redis->get($key);
            if ($cached !== false) {
                $result = json_decode($cached, true);
                self::$l1Cache[$key] = $result;
                if (function_exists('apcu_store')) apcu_store($key, $result, min($ttl, 60));
                return $result;
            }
        } catch (\Throwable $e) {}

        $result = $callback();
        self::$l1Cache[$key] = $result;

        try {
            $redis = redis();
            $redis->setex($key, $ttl, json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {}

        if (function_exists('apcu_store')) {
            apcu_store($key, $result, min($ttl, 60));
        }

        return $result;
    }

    public static function forget(string $key): void
    {
        unset(self::$l1Cache[$key]);
        if (function_exists('apcu_delete')) apcu_delete($key);
        try { redis()->del($key); } catch (\Throwable $e) {}
    }

    public static function flush(string $prefix): void
    {
        self::$l1Cache = [];
        if (function_exists('apcu_clear_cache')) apcu_clear_cache();
        try {
            $redis = redis();
            $keys = $redis->keys($prefix . '*') ?: [];
            foreach ($keys as $k) $redis->del($k);
        } catch (\Throwable $e) {}
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
