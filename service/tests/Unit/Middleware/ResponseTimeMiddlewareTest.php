<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\ResponseTimeMiddleware;

class ResponseTimeMiddlewareTest extends TestCase
{
    public function testAddsResponseTimeHeader(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\n\r\n");

        $response = (new ResponseTimeMiddleware())->process($request, function (Request $req) {
            return new Response(200, [], 'ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/^\d+ms$/', (string) $response->getHeader('X-Response-Time'));
    }
}
