<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 认证端点：login / me / refresh + AuthMiddleware 鉴权中间件。
 */

namespace Tests\Integration;

use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\controller\v1\AuthController;
use plugin\ads_api\middleware\AuthMiddleware;

class AuthApiTest extends ApiTestCase
{
    public function testLoginSuccess(): void
    {
        $response = (new AuthController())->login($this->makeRequest('POST', '/api/auth/login', [
            'username' => 'testuser',
            'password' => 'testpass',
        ]));

        $body = $this->assertSuccess($response);
        $this->assertNotEmpty($body['data']['access_token']);
        $this->assertEquals('Bearer', $body['data']['token_type']);
        $this->assertEquals('testuser', $body['data']['user']['username']);
    }

    public function testLoginWrongPassword(): void
    {
        $response = (new AuthController())->login($this->makeRequest('POST', '/api/auth/login', [
            'username' => 'testuser',
            'password' => 'wrong',
        ]));

        $this->assertError($response, 1001);
    }

    public function testLoginEmptyCredentials(): void
    {
        $response = (new AuthController())->login($this->makeRequest('POST', '/api/auth/login', [
            'username' => '',
            'password' => '',
        ]));

        $this->assertError($response, 1001);
    }

    public function testLoginUnknownUser(): void
    {
        $response = (new AuthController())->login($this->makeRequest('POST', '/api/auth/login', [
            'username' => 'nobody',
            'password' => 'whatever',
        ]));

        $this->assertError($response, 1001);
    }

    public function testMeReturnsCurrentUser(): void
    {
        $request = $this->authedRequest('GET', '/api/auth/me');

        $body = $this->assertSuccess((new AuthController())->me($request));
        $this->assertEquals($this->userId, $body['data']['id']);
        $this->assertEquals('testuser', $body['data']['username']);
    }

    public function testMeWithoutLogin(): void
    {
        $request = $this->makeRequest('GET', '/api/auth/me');
        $body = $this->json((new AuthController())->me($request));
        $this->assertEquals(401, $body['code']);
    }

    public function testRefreshTokenSuccess(): void
    {
        $token = $this->token();
        $request = $this->makeRequest('POST', '/api/auth/refresh', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $body = $this->assertSuccess((new AuthController())->refreshToken($request));
        $this->assertNotEmpty($body['data']['access_token']);
    }

    public function testRefreshTokenWithoutHeader(): void
    {
        $request = $this->makeRequest('POST', '/api/auth/refresh');
        $body = $this->json((new AuthController())->refreshToken($request));
        $this->assertEquals(401, $body['code']);
    }

    public function testRefreshTokenInvalid(): void
    {
        $request = $this->makeRequest('POST', '/api/auth/refresh', [], [
            'Authorization' => 'Bearer not-a-real-token',
        ]);

        $body = $this->json((new AuthController())->refreshToken($request));
        $this->assertEquals(401, $body['code']);
    }

    public function testLogoutSucceedsWithBearerToken(): void
    {
        $request = $this->authedRequest('POST', '/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $this->token(),
        ]);
        $body = $this->assertSuccess((new AuthController())->logout($request));
        $this->assertSame('已退出', $body['message']);
    }

    public function testLogoutWithoutTokenStillSucceeds(): void
    {
        $request = $this->authedRequest('POST', '/api/auth/logout');
        $body = $this->assertSuccess((new AuthController())->logout($request));
        $this->assertSame('已退出', $body['message']);
    }

    public function testLogoutRouteIsRegistered(): void
    {
        $routes = (string) file_get_contents(__DIR__ . '/../../plugin/ads-api/config/route.php');
        $this->assertStringContainsString(
            "Webman\\Route::post('/auth/logout', [AuthController::class, 'logout']);",
            $routes
        );
    }

    public function testMiddlewareRejectsMissingToken(): void
    {
        $middleware = new AuthMiddleware();
        $request = $this->makeRequest('GET', '/api/accounts');
        $response = $middleware->process($request, fn (Request $r) => new Response(200));

        $this->assertEquals(401, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertEquals(401, $body['code']);
    }

    public function testMiddlewareRejectsInvalidToken(): void
    {
        $middleware = new AuthMiddleware();
        $request = $this->makeRequest('GET', '/api/accounts', [], [
            'Authorization' => 'Bearer invalid.token.value',
        ]);
        $response = $middleware->process($request, fn (Request $r) => new Response(200));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testMiddlewareAcceptsValidTokenAndSetsUser(): void
    {
        $middleware = new AuthMiddleware();
        $request = $this->makeRequest('GET', '/api/accounts', [], [
            'Authorization' => 'Bearer ' . $this->token(),
        ]);

        $seen = null;
        $response = $middleware->process($request, function (Request $r) use (&$seen) {
            $seen = ['uid' => $r->userId];
            return new Response(200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['uid' => $this->userId], $seen);
    }
}
