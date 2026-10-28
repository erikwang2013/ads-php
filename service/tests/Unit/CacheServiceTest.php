<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CacheService::remember TTL 回归：TTL 内命中不执行回调，过期后重新执行。
 * 测试环境 redis() 为 null 桩，仅验证 L1/APCu 路径。
 */

namespace Tests\Unit;

use erik\support\CacheService;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    public function testRememberCacheHitAndExpiry(): void
    {
        $key = 'ttl-test';
        CacheService::forget($key);

        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return 'cached-value';
        };

        try {
            $this->assertSame('cached-value', CacheService::remember($key, 1, $callback));
            $this->assertSame('cached-value', CacheService::remember($key, 1, $callback));
            $this->assertSame(1, $calls, 'TTL 内两次调用应只执行一次回调');

            sleep(2);

            $this->assertSame('cached-value', CacheService::remember($key, 1, $callback));
            $this->assertSame(2, $calls, 'TTL 过期后应重新执行回调');
        } finally {
            CacheService::forget($key);
        }
    }

    public function testFlushPrefixInvalidatesMatchingKeys(): void
    {
        $key = 'cache:accounts:flush-test';
        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return 'value';
        };

        try {
            CacheService::remember($key, 60, $callback);
            CacheService::remember($key, 60, $callback);
            $this->assertSame(1, $calls);

            CacheService::flush('cache:accounts:');
            CacheService::remember($key, 60, $callback);
            $this->assertSame(2, $calls);
        } finally {
            CacheService::forget($key);
        }
    }
}
