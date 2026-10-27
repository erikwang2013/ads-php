<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * ReplayGuardMiddleware 测试：跳过路径、web 端放行、缺 nonce/timestamp 放行、
 * 时间戳超窗拦截；Redis 不可用时降级放行。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\ReplayGuardMiddleware;

class ReplayGuardMiddlewareTest extends TestCase
{
    private function makeRequest(string $path, array $extraHeaders = []): Request
    {
        $headers = "Host: localhost\r\n";
        foreach ($extraHeaders as $k => $v) {
            $headers .= "$k: $v\r\n";
        }
        return new Request("POST $path HTTP/1.1\r\n$headers\r\n");
    }

    private function process(Request $request): Response
    {
        return (new ReplayGuardMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });
    }

    public function testSkipsHealthPath(): void
    {
        $response = $this->process($this->makeRequest('/health', ['X-Client-Platform' => 'android']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSkipsWebPlatform(): void
    {
        $response = $this->process($this->makeRequest('/api/x', ['X-Client-Platform' => 'web']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAllowsRequestWithoutNonceAndTimestamp(): void
    {
        $response = $this->process($this->makeRequest('/api/x', ['X-Client-Platform' => 'android']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBlocksStaleTimestamp(): void
    {
        $stale = time() - 600;
        $request = $this->makeRequest('/api/x', [
            'X-Client-Platform' => 'android',
            'X-Nonce'           => 'nonce-1',
            'X-Timestamp'       => (string) $stale,
        ]);
        // clientPlatform 由 ClientPlatformMiddleware 注入，此处模拟其效果
        $request->clientPlatform = 'android';

        $response = $this->process($request);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('expired', json_decode($response->rawBody(), true)['message']);
    }

    public function testAllowsFreshTimestampWithoutRedis(): void
    {
        // Redis 不可用（测试桩返回 null）→ 异常被吞 → 放行
        $request = $this->makeRequest('/api/x', [
            'X-Client-Platform' => 'android',
            'X-Nonce'           => 'nonce-2',
            'X-Timestamp'       => (string) time(),
        ]);

        $response = $this->process($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
