<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ClientPlatformMiddleware implements MiddlewareInterface
{
    public const PLATFORMS = [
        'web', 'ios', 'android',
        'ipados', 'macos', 'windows', 'linux',
        'harmonyos',
    ];

    public function process(Request $request, callable $handler): Response
    {
        $platform = strtolower($request->header('X-Client-Platform', 'web'));

        if (!in_array($platform, self::PLATFORMS, true)) {
            $platform = 'web';
        }

        $request->clientPlatform = $platform;

        return $handler($request);
    }
}
