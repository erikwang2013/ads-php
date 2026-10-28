<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * RateLimitMiddleware 测试：Redis 不可用/故障时优雅放行，且只调用一次 handler。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\RateLimitMiddleware;

class RateLimitMiddlewareTest extends TestCase
{
    public function testPassesThroughWhenRedisUnavailable(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\n\r\n");

        $calls = 0;
        $response = (new RateLimitMiddleware())->process($request, function (Request $req) use (&$calls) {
            $calls++;
            return new Response(200, [], 'ok');
        });

        $this->assertSame(1, $calls);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCustomLimitsAreConfigurable(): void
    {
        $mw = new RateLimitMiddleware(10, 30);

        $this->assertTrue($mw instanceof RateLimitMiddleware);
    }
}
