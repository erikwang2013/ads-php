<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\AttackGuardMiddleware;

class AttackGuardMiddlewareTest extends TestCase
{
    private function makeRequest(string $method, string $path, array $extraHeaders = []): Request
    {
        $headers = "Host: localhost\r\n";
        foreach ($extraHeaders as $k => $v) {
            $headers .= "$k: $v\r\n";
        }
        return new Request("$method $path HTTP/1.1\r\n$headers\r\n");
    }

    private function process(Request $request): Response
    {
        return (new AttackGuardMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });
    }

    public function testAllowsCleanRequest(): void
    {
        $response = $this->process($this->makeRequest('GET', '/api/campaigns'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBlocksPathTraversal(): void
    {
        // 中间件仅拦截字面量穿越标记（../、..\、\0、%00）；URL 编码形式（%2e%2e）
        // 不会被 webman 路由解码，生产环境直接 404，不在本层防御范围
        foreach (['/api/../etc/passwd', '/api/..\\..\\win', '/api/x%00y'] as $path) {
            $response = $this->process($this->makeRequest('GET', $path));
            $this->assertEquals(403, $response->getStatusCode(), "expected block for {$path}");
        }
    }

    public function testBlocksXssInQueryString(): void
    {
        $response = $this->process($this->makeRequest('GET', '/api/x?q=<script>alert(1)</script>'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testBlocksOnEventHandlerPattern(): void
    {
        $response = $this->process($this->makeRequest('GET', '/api/x?q=onclick=evil'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testBlocksInvalidContentType(): void
    {
        $request = $this->makeRequest('POST', '/api/x', ['Content-Type' => 'text/xml']);
        $response = $this->process($request);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAllowsJsonContentType(): void
    {
        $request = $this->makeRequest('POST', '/api/x', ['Content-Type' => 'application/json']);
        $response = $this->process($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBlocksOversizedBody(): void
    {
        $body = str_repeat('a', 10485760 + 1);
        $request = new Request("POST /api/x HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body);

        $response = $this->process($request);
        $this->assertEquals(403, $response->getStatusCode());
    }
}
