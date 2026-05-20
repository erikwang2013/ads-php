<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Integration;

use Tests\TestCase;
use Webman\Http\Request;
use plugin\ads_api\controller\v1\AuthController;
use Illuminate\Database\Capsule\Manager as DB;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed a test user
        DB::table('admin_users')->updateOrInsert(
            ['username' => 'testuser'],
            [
                'id'       => 9999,
                'username' => 'testuser',
                'password' => password_hash('testpass', PASSWORD_BCRYPT),
                'name'     => 'Test User',
                'role_id'  => 1,
                'status'   => 1,
            ]
        );

        // Set JWT secret for testing
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
}
