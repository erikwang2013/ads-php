<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CorsMiddleware 测试：调试模式允许所有来源；关闭调试后按白名单校验
 * 并区分是否携带 Allow-Credentials。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\CorsMiddleware;

class CorsMiddlewareTest extends TestCase
{
    /**
     * phpunit.xml 会把 APP_DEBUG=true 注入 $_SERVER/$_ENV，且 dotenv 读取
     * 优先级为 $_SERVER > $_ENV > getenv，故 putenv 单独设置无效——须三处同时覆盖。
     */
    private function setDebug(bool $debug): void
    {
        $value = $debug ? 'true' : 'false';
        putenv("APP_DEBUG=$value");
        $_ENV['APP_DEBUG'] = $value;
        $_SERVER['APP_DEBUG'] = $value;
    }

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
        return (new CorsMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });
    }

    public function testDebugModeAllowsAnyOriginOnPreflight(): void
    {
        $this->setDebug(true);

        $response = $this->process($this->makeRequest('OPTIONS', '/api/x', ['Origin' => 'http://any.example']));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame('*', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertNull($response->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame('86400', $response->getHeader('Access-Control-Max-Age'));
    }

    public function testDebugModeSetsOriginHeaderOnNormalResponse(): void
    {
        $this->setDebug(true);

        $response = $this->process($this->makeRequest('GET', '/api/x', ['Origin' => 'http://any.example']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('*', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testProductionModeEchoesWhitelistedOriginWithCredentials(): void
    {
        $this->setDebug(false);

        try {
            $response = $this->process($this->makeRequest('POST', '/api/x', ['Origin' => 'http://127.0.0.1:8788']));

            $this->assertSame('http://127.0.0.1:8788', $response->getHeader('Access-Control-Allow-Origin'));
            $this->assertSame('true', $response->getHeader('Access-Control-Allow-Credentials'));
        } finally {
            $this->setDebug(true);
        }
    }

    public function testProductionModeOmitsHeaderForUnknownOrigin(): void
    {
        $this->setDebug(false);

        try {
            $response = $this->process($this->makeRequest('POST', '/api/x', ['Origin' => 'http://evil.example']));

            $this->assertNull($response->getHeader('Access-Control-Allow-Origin'));
        } finally {
            $this->setDebug(true);
        }
    }
}
