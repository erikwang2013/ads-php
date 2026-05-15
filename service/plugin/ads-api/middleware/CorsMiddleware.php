<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if ($request->method() === 'OPTIONS') {
            return new Response(204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Tenant-Id, X-Encrypted',
                'Access-Control-Max-Age' => '86400',
            ], '');
        }

        $response = $handler($request);
        $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Tenant-Id, X-Encrypted',
        ]);
        return $response;
    }
}
