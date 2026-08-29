<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 平台管理员守卫:tenant 1 放行,其余租户 403(平台级配置不可跨租户接管)。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\AdminMiddleware;

class AdminMiddlewareTest extends TestCase
{
    private function process(Request $request): Response
    {
        return (new AdminMiddleware())->process($request, fn (Request $req) => new Response(200, [], 'ok'));
    }

    private function makeRequest(): Request
    {
        return new Request("GET /api/admin/cdn/providers HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    public function testAllowsTenantOne(): void
    {
        $request = $this->makeRequest();
        $request->tenantId = 1;
        $this->assertEquals(200, $this->process($request)->getStatusCode());
    }

    public function testRejectsOtherTenants(): void
    {
        $request = $this->makeRequest();
        $request->tenantId = 2;
        $response = $this->process($request);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(403, json_decode($response->rawBody(), true)['code']);
    }
}
