<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\VersionMiddleware;

class VersionMiddlewareTest extends TestCase
{
    protected function makeRequest(string $method = 'GET', string $path = '/api/test', array $extraHeaders = []): Request
    {
        $headers = "Host: localhost\r\n";
        foreach ($extraHeaders as $k => $v) {
            $headers .= "$k: $v\r\n";
        }
        return new Request("$method $path HTTP/1.1\r\n$headers\r\n");
    }

    public function testSetsDefaultVersionWhenHeaderMissing(): void
    {
        $request = $this->makeRequest();

        $handler = function (Request $req) {
            return new Response(200, [], json_encode(['version' => $req->apiVersion]));
        };

        $mw = new VersionMiddleware();
        $response = $mw->process($request, $handler);

        $body = json_decode($response->rawBody(), true);
        $this->assertEquals('v1', $body['version']);
    }

    public function testRejectsUnknownVersion(): void
    {
        $request = $this->makeRequest(extraHeaders: ['X-API-Version' => 'v99']);

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new VersionMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testRejectsInvalidVersionFormat(): void
    {
        $request = $this->makeRequest(extraHeaders: ['X-API-Version' => '../v1']);

        $handler = function (Request $req) {
            return new Response(200, [], 'ok');
        };

        $mw = new VersionMiddleware();
        $response = $mw->process($request, $handler);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAcceptsV1(): void
    {
        $request = $this->makeRequest(extraHeaders: ['X-API-Version' => 'v1']);

        $handler = function (Request $req) {
            return new Response(200, [], json_encode(['version' => $req->apiVersion]));
        };

        $mw = new VersionMiddleware();
        $response = $mw->process($request, $handler);

        $body = json_decode($response->rawBody(), true);
        $this->assertEquals('v1', $body['version']);
    }
}
