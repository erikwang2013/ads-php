<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

class AdapterRegistry
{
    protected static array $adapters = [];

    public static function register(PlatformAdapter $adapter): void
    {
        static::$adapters[$adapter->code()] = $adapter;
    }

    public static function get(string $code): ?PlatformAdapter
    {
        return static::$adapters[$code] ?? null;
    }

    public static function all(): array
    {
        $list = [];
        foreach (static::$adapters as $adapter) {
            $list[] = [
                'code'         => $adapter->code(),
                'name'         => $adapter->name(),
                'capabilities' => $adapter->capabilities(),
            ];
        }
        return $list;
    }

    public static function has(string $code): bool
    {
        return isset(static::$adapters[$code]);
    }
}
