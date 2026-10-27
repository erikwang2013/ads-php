<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * AuthMiddleware 测试：缺 Authorization / 非 Bearer / 无效 token 均返回 401。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\AuthMiddleware;

class AuthMiddlewareTest extends TestCase
{
    private function makeRequest(array $extraHeaders = []): Request
    {
        $headers = "Host: localhost\r\n";
        foreach ($extraHeaders as $k => $v) {
            $headers .= "$k: $v\r\n";
        }
        return new Request("GET /api/x HTTP/1.1\r\n$headers\r\n");
    }

    public function testRejectsMissingAuthorizationHeader(): void
    {
        $response = (new AuthMiddleware())->process($this->makeRequest(), function (Request $req) {
            return new Response(200, [], 'ok');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRejectsNonBearerScheme(): void
    {
        $response = (new AuthMiddleware())->process(
            $this->makeRequest(['Authorization' => 'Basic dXNlcjpwYXNz']),
            function (Request $req) { return new Response(200, [], 'ok'); }
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRejectsInvalidToken(): void
    {
        $response = (new AuthMiddleware())->process(
            $this->makeRequest(['Authorization' => 'Bearer not.a.valid.token']),
            function (Request $req) { return new Response(200, [], 'ok'); }
        );

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->rawBody(), true);
        $this->assertSame(401, $body['code']);
    }
}
