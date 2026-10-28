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

    public function testAllowsOriginFromCorsAllowedOrigins(): void
    {
        // Vite 开发场景：APP_URL 是 0.0.0.0，浏览器 Origin 是 localhost:5173
        putenv('APP_URL=http://0.0.0.0:8788');
        putenv('CORS_ALLOWED_ORIGINS=http://localhost:5173');
        try {
            $response = $this->process($this->makeRequest('POST', '/api/x', ['Origin' => 'http://localhost:5173']));
            $this->assertEquals(200, $response->getStatusCode());
        } finally {
            putenv('APP_URL');
            putenv('CORS_ALLOWED_ORIGINS');
        }
    }

    public function testAllowsInstallerPathsFromAnyOrigin(): void
    {
        // 安装器在 APP_URL 配置前运行，Origin 与默认 APP_URL 不同也应放行
        foreach (['/install', '/api/install/check', '/api/install/run', '/api/install/status'] as $path) {
            $response = $this->process($this->makeRequest('POST', $path, ['Origin' => 'http://localhost']));
            $this->assertEquals(200, $response->getStatusCode(), "expected installer path {$path} to bypass origin check");
        }
    }
}
