<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\ClientPlatformMiddleware;

class ClientPlatformMiddlewareTest extends TestCase
{
    private function process(Request $request, ?string &$platform): Response
    {
        $response = (new ClientPlatformMiddleware())->process($request, function (Request $req) use (&$platform) {
            $platform = $req->clientPlatform;
            return new Response(200, [], 'ok');
        });
        return $response;
    }

    public function testNormalizesKnownPlatform(): void
    {
        foreach (['web', 'ios', 'android', 'harmonyos'] as $p) {
            $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\nX-Client-Platform: $p\r\n\r\n");
            $platform = null;
            $this->process($request, $platform);
            $this->assertSame($p, $platform);
        }
    }

    public function testDefaultsToWebWhenHeaderMissing(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $platform = null;
        $this->process($request, $platform);
        $this->assertSame('web', $platform);
    }

    public function testFallsBackToWebForUnknownPlatform(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\nX-Client-Platform: symbian\r\n\r\n");
        $platform = null;
        $this->process($request, $platform);
        $this->assertSame('web', $platform);
    }
}
