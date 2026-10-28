<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Integration;

use Tests\TestCase;
use plugin\ads_api\controller\v1\HealthController;

class HealthControllerTest extends TestCase
{
    public function testPingReturnsPong(): void
    {
        $controller = new HealthController();
        $response = $controller->ping();

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->rawBody(), true);
        $this->assertTrue($body['pong']);
    }

    public function testHealthReturnsStatus(): void
    {
        $controller = new HealthController();
        $response = $controller->health();

        $this->assertContains($response->getStatusCode(), [200, 503]);
        $body = json_decode($response->rawBody(), true);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('checks', $body);
        $this->assertArrayHasKey('database', $body['checks']);
        $this->assertArrayHasKey('redis', $body['checks']);

        // 探活端点不得泄露异常/连接串细节
        $this->assertContains($body['checks']['database'], ['ok', 'unavailable']);
        $this->assertContains($body['checks']['redis'], ['ok', 'unavailable']);
        $this->assertStringNotContainsString('error:', $response->rawBody());
        $this->assertStringNotContainsString('SQLSTATE', $response->rawBody());
        $this->assertStringNotContainsString('Exception', $response->rawBody());
    }
}
