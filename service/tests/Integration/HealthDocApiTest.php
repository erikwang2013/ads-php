<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 健康检查 / 文档 / 验证码（无需认证的公开端点）。
 */

namespace Tests\Integration;

use plugin\ads_api\controller\v1\HealthController;
use plugin\ads_api\controller\v1\DocController;
use plugin\ads_api\controller\v1\CaptchaController;

class HealthDocApiTest extends ApiTestCase
{
    public function testPing(): void
    {
        $response = (new HealthController())->ping();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['pong' => true], $this->json($response));
    }

    public function testHealth(): void
    {
        // 测试环境无 Redis → 健康检查如实降级为 503 degraded（非 ApiResponse 包装）
        $response = (new HealthController())->health();
        $this->assertEquals(503, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertEquals('degraded', $body['status']);
        $this->assertEquals('ok', $body['checks']['database']);
        $this->assertEquals('unavailable', $body['checks']['redis']);
    }

    public function testDocs(): void
    {
        $response = (new DocController())->index();
        $this->assertEquals(200, $response->getStatusCode());
        $html = $response->rawBody();
        $this->assertStringContainsString('/health', $html);
        $this->assertStringContainsString('/api/v1/auth/login', $html);
    }

    public function testCaptchaGenerate(): void
    {
        // 生产 bug #5 已修复（2026-08-27）：CaptchaService 改用真实 Poster 包 API（GD + Storage）
        $body = $this->assertSuccess((new CaptchaController())->generate());
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertArrayHasKey('bg_image', $body['data']);
        $this->assertNotSame('', $body['data']['token']);
    }

    public function testCaptchaVerifyMissingToken(): void
    {
        $request = $this->authedRequest('POST', '/api/captcha/verify');
        $this->assertError((new CaptchaController())->verify($request), 1);
    }

    public function testCaptchaVerifyInvalidToken(): void
    {
        // 生产 bug #5 已修复：无效 token → data.valid=false（此前为未捕获异常）
        $request = $this->authedRequest('POST', '/api/captcha/verify', ['token' => 'invalid-token', 'offset_x' => 10]);
        $body = $this->assertSuccess((new CaptchaController())->verify($request));
        $this->assertFalse($body['data']['valid']);
    }
}
