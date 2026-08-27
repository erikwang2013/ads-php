<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * SessionLimitMiddleware 测试：跳过路径、无 userId 放行；Redis 不可用时放行。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\SessionLimitMiddleware;

class SessionLimitMiddlewareTest extends TestCase
{
    private function process(Request $request): Response
    {
        return (new SessionLimitMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });
    }

    public function testSkipsHealthPath(): void
    {
        $request = new Request("GET /health HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $this->assertEquals(200, $this->process($request)->getStatusCode());
    }

    public function testPassesThroughWhenNoUserId(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $this->assertEquals(200, $this->process($request)->getStatusCode());
    }

    public function testPassesThroughWhenRedisUnavailable(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\nAuthorization: Bearer abc\r\n\r\n");
        $request->userId = 7;

        $this->assertEquals(200, $this->process($request)->getStatusCode());
    }
}
