<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * LoginThrottleMiddleware 测试：非登录路径/空用户名放行；Redis 不可用时放行。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\LoginThrottleMiddleware;

class LoginThrottleMiddlewareTest extends TestCase
{
    private function makeRequest(string $method, string $path, string $body = ''): Request
    {
        $headers = "Host: localhost\r\n";
        if ($body !== '') {
            $headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $headers .= "Content-Length: " . strlen($body) . "\r\n";
        }
        return new Request("$method $path HTTP/1.1\r\n$headers\r\n$body");
    }

    private function process(Request $request): Response
    {
        return (new LoginThrottleMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });
    }

    public function testSkipsNonLoginPath(): void
    {
        $response = $this->process($this->makeRequest('POST', '/api/campaigns'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSkipsEmptyUsername(): void
    {
        $response = $this->process($this->makeRequest('POST', '/api/auth/login', 'username=&password=x'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPassesThroughWhenRedisUnavailable(): void
    {
        $response = $this->process($this->makeRequest('POST', '/api/auth/login', 'username=admin&password=wrong'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
