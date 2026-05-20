<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class VersionMiddleware implements MiddlewareInterface
{
    protected const SUPPORTED_VERSIONS = ['v1'];

    public function process(Request $request, callable $handler): Response
    {
        $version = $request->header('X-API-Version', 'v1');

        if (!preg_match('/^[a-z0-9]+$/i', $version)) {
            return new Response(400, ['Content-Type' => 'application/json'],
                json_encode(['code' => 400, 'message' => 'Invalid API version format'], JSON_UNESCAPED_UNICODE));
        }

        if (!in_array($version, self::SUPPORTED_VERSIONS, true)) {
            return new Response(400, ['Content-Type' => 'application/json'],
                json_encode(['code' => 400, 'message' => "API version '$version' is not supported"], JSON_UNESCAPED_UNICODE));
        }

        $request->apiVersion = $version;
        return $handler($request);
    }
}
