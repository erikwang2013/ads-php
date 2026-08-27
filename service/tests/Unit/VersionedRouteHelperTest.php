<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * route_helpers.php versioned() 测试：版本路由解析、版本不存在 400、
 * 无 Request 参数的方法签名探测。
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;

class VersionedRouteHelperTest extends TestCase
{
    private const V1 = 'Tests\Unit\Fixtures\controller\v1\FakeController';

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../plugin/ads-api/route_helpers.php';
        // versioned() 通过 \support\Container::instance()（= Config::get('container')）
        // 解析控制器；测试进程未加载 webman config → 需显式加载容器配置
        if (\Webman\Config::get('container') === null) {
            \Webman\Config::load(config_path(), ['route']);
        }
    }

    private function makeRequest(string $version): Request
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $request->apiVersion = $version;
        return $request;
    }

    public function testInvokesV1ControllerByDefault(): void
    {
        $response = \versioned(self::V1, 'index')( $this->makeRequest('v1'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('v1:v1', $response->rawBody());
    }

    public function testSwitchesToV2ControllerByVersion(): void
    {
        $response = \versioned(self::V1, 'index')($this->makeRequest('v2'));

        $this->assertSame('v2:v2', $response->rawBody());
    }

    public function testReturns400ForUnavailableVersion(): void
    {
        $response = \versioned(self::V1, 'index')($this->makeRequest('v9'));

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->rawBody(), true);
        $this->assertStringContainsString('v9', $body['message']);
    }

    public function testMethodWithoutRequestArgStillInvoked(): void
    {
        $response = \versioned(self::V1, 'noRequestArg')($this->makeRequest('v1'));

        $this->assertSame('v1-no-arg', $response->rawBody());
    }
}
