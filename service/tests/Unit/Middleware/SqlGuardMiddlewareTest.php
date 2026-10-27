<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\SqlGuardMiddleware;

class SqlGuardMiddlewareTest extends TestCase
{
    public function testBlocksUnionSelect(): void
    {
        $body = json_encode(['q' => '1 UNION SELECT * FROM users']);
        $buffer = "POST /api/test HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n$body";
        $request = new Request($buffer);

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new SqlGuardMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testBlocksDropTable(): void
    {
        $body = json_encode(['q' => 'x; DROP TABLE users;--']);
        $buffer = "POST /api/test HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n$body";
        $request = new Request($buffer);

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new SqlGuardMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAllowsCleanInput(): void
    {
        $body = json_encode(['q' => 'normal search term']);
        $buffer = "POST /api/test HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n$body";
        $request = new Request($buffer);

        $called = false;
        $handler = function (Request $req) use (&$called) {
            $called = true;
            return new Response(200, [], 'ok');
        };

        $mw = new SqlGuardMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
