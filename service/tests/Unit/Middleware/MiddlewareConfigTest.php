<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 全局中间件配置：webman 2.x 的跨应用桶键为 '@'，
 * 使用其他键（如 'global'）会被当成应用名而全部失效。
 */

namespace Tests\Unit\Middleware;

use plugin\ads_api\controller\v1\AuthController;
use Tests\TestCase;
use Webman\Middleware;

class MiddlewareConfigTest extends TestCase
{
    public function testGlobalMiddlewareChainIsNotEmpty(): void
    {
        $config = require __DIR__ . '/../../../config/middleware.php';

        $this->assertArrayHasKey('@', $config);
        $this->assertArrayNotHasKey('global', $config);
        $this->assertCount(12, $config['@']);

        Middleware::load($config);
        $chain = Middleware::getMiddleware('', '', [AuthController::class, 'me'], null);

        // 配置中的 12 个全局中间件应全部进入链
        $this->assertCount(count($config['@']), $chain);
        $this->assertNotEmpty($chain);
    }
}
