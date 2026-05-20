<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\SecurityHeadersMiddleware;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAddsSecurityHeaders(): void
    {
        $request = new Request("GET /api/test HTTP/1.1\r\nHost: localhost\r\n\r\n");

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new SecurityHeadersMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertEquals('nosniff', $response->getHeader('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->getHeader('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->getHeader('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->getHeader('Referrer-Policy'));
        $this->assertEquals('none', $response->getHeader('X-Permitted-Cross-Domain-Policies'));
    }

    public function testDoesNotAddHstsByDefault(): void
    {
        $request = new Request("GET /api/test HTTP/1.1\r\nHost: localhost\r\n\r\n");

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new SecurityHeadersMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertEmpty($response->getHeader('Strict-Transport-Security'));
    }

    public function testAddsHstsWithForwardedProto(): void
    {
        $request = new Request("GET /api/test HTTP/1.1\r\nHost: localhost\r\nX-Forwarded-Proto: https\r\n\r\n");

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new SecurityHeadersMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertNotEmpty($response->getHeader('Strict-Transport-Security'));
        $this->assertStringContainsString('max-age=31536000', $response->getHeader('Strict-Transport-Security'));
    }
}
