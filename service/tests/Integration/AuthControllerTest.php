<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Integration;

use Erikwang2013\Poster\PosterConfig;
use Erikwang2013\Poster\Storage\FileStorage;
use Tests\TestCase;
use Webman\Http\Request;
use plugin\ads_api\controller\v1\AuthController;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('JWT_SECRET=test-jwt-secret-at-least-16-chars-long');

        // Bypass I18n request dependency in test environment
        \app\support\ApiResponse::setLang('zh-CN');
    }

    protected function makeRequest(string $method, string $path, array $body = [], array $extraHeaders = []): Request
    {
        $headers = "Host: localhost\r\n";
        foreach ($extraHeaders as $k => $v) {
            $headers .= "$k: $v\r\n";
        }
        $jsonBody = json_encode($body);
        if ($method === 'POST' && !empty($body)) {
            $headers .= "Content-Type: application/json\r\n";
            $headers .= "Content-Length: " . strlen($jsonBody) . "\r\n";
        }
        return new Request("$method $path HTTP/1.1\r\n$headers\r\n$jsonBody");
    }

    public function testLoginWithValidCredentials(): void
    {
        $request = $this->makeRequest('POST', '/api/auth/login', [
            'username' => 'testuser',
            'password' => 'testpass',
        ]);

        $controller = new AuthController();
        $response = $controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->rawBody(), true);
        $this->assertEquals(0, $body['code']);
        $this->assertNotEmpty($body['data']['access_token']);
        $this->assertEquals('testuser', $body['data']['user']['username']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $request = $this->makeRequest('POST', '/api/auth/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $controller = new AuthController();
        $response = $controller->login($request);

        $body = json_decode($response->rawBody(), true);
        $this->assertEquals(1001, $body['code']);
    }

    public function testLoginWithEmptyCredentials(): void
    {
        $request = $this->makeRequest('POST', '/api/auth/login', [
            'username' => '',
            'password' => '',
        ]);

        $controller = new AuthController();
        $response = $controller->login($request);

        $body = json_decode($response->rawBody(), true);
        $this->assertEquals(1001, $body['code']);
    }

    public function testLoginRequiresCaptchaWhenEnabled(): void
    {
        $previous = getenv('CAPTCHA_ENABLED');
        putenv('CAPTCHA_ENABLED=true');
        // 固定文件存储，避免测试机上有 Redis 时走 RedisStorage（本测试不依赖外部服务）
        PosterConfig::reset();
        PosterConfig::merge(['captcha' => ['storage' => 'file']]);
        $storage = new FileStorage();
        $token = bin2hex(random_bytes(16));
        $storage->set($token, ['x' => 120, 'type' => 'slider', 'attempts' => 0, 'created_at' => time()]);

        try {
            $body = $this->login(['username' => 'testuser', 'password' => 'testpass']);
            $this->assertSame(1, $body['code']);
            $this->assertSame('验证码不能为空', $body['message']);

            $body = $this->login([
                'username'       => 'testuser',
                'password'       => 'testpass',
                'captcha_token'  => $token,
                'captcha_offset' => 120,
            ]);
            $this->assertSame(0, $body['code']);
            $this->assertNotEmpty($body['data']['access_token']);
        } finally {
            $storage->del($token);
            PosterConfig::reset();
            putenv($previous === false ? 'CAPTCHA_ENABLED=false' : 'CAPTCHA_ENABLED=' . $previous);
        }
    }

    public function testLoginWithoutCaptchaWhenDisabled(): void
    {
        $previous = getenv('CAPTCHA_ENABLED');
        putenv('CAPTCHA_ENABLED=false');
        try {
            $body = $this->login(['username' => 'testuser', 'password' => 'testpass']);
            $this->assertSame(0, $body['code']);
            $this->assertNotEmpty($body['data']['access_token']);
        } finally {
            putenv($previous === false ? 'CAPTCHA_ENABLED=false' : 'CAPTCHA_ENABLED=' . $previous);
        }
    }

    private function login(array $payload): array
    {
        $response = (new AuthController())->login($this->makeRequest('POST', '/api/auth/login', $payload));
        return json_decode($response->rawBody(), true) ?? [];
    }
}
