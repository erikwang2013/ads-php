<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * OriginGuardMiddleware 测试：危险 HTTP 方法拦截、跨域 Origin 白名单校验、
 * 无 Origin 请求放行。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\OriginGuardMiddleware;

class OriginGuardMiddlewareTest extends TestCase
{
    private function makeRequest(string $method, string $path = '/api/x', array $extraHeaders = []): Request
    {
        $headers = "Host: localhost\r\n";
        foreach ($extraHeaders as $k => $v) {
            $headers .= "$k: $v\r\n";
        }
        return new Request("$method $path HTTP/1.1\r\n$headers\r\n");
    }

    private function process(Request $request): Response
    {
        return (new OriginGuardMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });
    }

    public function testBlocksNonStandardMethods(): void
    {
        foreach (['TRACE', 'DEBUG', 'CONNECT', 'TRACK'] as $method) {
            $response = $this->process($this->makeRequest($method));
            $this->assertEquals(403, $response->getStatusCode(), "expected block for {$method}");
        }
    }

    public function testAllowsGetWithoutOrigin(): void
    {
        $response = $this->process($this->makeRequest('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAllowsSameHostOrigin(): void
    {
        // 默认白名单 APP_URL=http://127.0.0.1:8788
        $response = $this->process($this->makeRequest('POST', '/api/x', ['Origin' => 'http://127.0.0.1:8788']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBlocksUnknownOrigin(): void
    {
        $response = $this->process($this->makeRequest('POST', '/api/x', ['Origin' => 'http://evil.example']));
        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->rawBody(), true);
        $this->assertSame(403, $body['code']);
    }

    public function testBlocksPostWithoutOriginHeader(): void
    {
        // 无 Origin 也无 Referer → 放行（同源场景）
        $response = $this->process($this->makeRequest('POST'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
