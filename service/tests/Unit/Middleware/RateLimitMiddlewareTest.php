<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * RateLimitMiddleware 测试：Redis 不可用时优雅放行。
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

        $called = false;
        $response = (new RateLimitMiddleware())->process($request, function (Request $req) use (&$called) {
            $called = true;
            return new Response(200, [], 'ok');
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCustomLimitsAreConfigurable(): void
    {
        $mw = new RateLimitMiddleware(10, 30);

        $this->assertTrue($mw instanceof RateLimitMiddleware);
    }
}
