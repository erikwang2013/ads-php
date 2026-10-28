<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CacheService — three-level cache: L1 (process memory) + L2 (APCu) + L3 (Redis).
 *
 * L1 ($l1Cache static array): per-process memory, fastest. Best for hot keys.
 * L2 (APCu): shared across workers, sub-millisecond access, short TTL (min 60s).
 * L3 (Redis): shared across processes, persistent. Falls through on miss.
 */

namespace erik\support;

class CacheService
{
    protected static int $defaultTtl = 300;
    protected static array $l1Cache = [];

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (isset(self::$l1Cache[$key])) {
            if (self::$l1Cache[$key]['expires_at'] > time()) {
                return self::$l1Cache[$key]['value'];
            }
            unset(self::$l1Cache[$key]);
        }

        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($key, $hit);
            if ($hit) {
                self::$l1Cache[$key] = ['expires_at' => time() + $ttl, 'value' => $cached];
                return $cached;
            }
        }

        try {
            $redis = redis();
            $cached = $redis->get($key);
            if ($cached !== false) {
                $result = json_decode($cached, true);
                self::$l1Cache[$key] = ['expires_at' => time() + $ttl, 'value' => $result];
                if (function_exists('apcu_store')) apcu_store($key, $result, min($ttl, 60));
                return $result;
            }
        } catch (\Throwable $e) {}

        $result = $callback();
        self::$l1Cache[$key] = ['expires_at' => time() + $ttl, 'value' => $result];

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

    public static function dashboardKey(string $dateStart, string $dateEnd): string
    {
        return "cache:dashboard:{$dateStart}:{$dateEnd}";
    }
}
