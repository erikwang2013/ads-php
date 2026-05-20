<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ResponseTimeMiddleware implements MiddlewareInterface
{
    protected int $slowThreshold = 1000;

    public function process(Request $request, callable $handler): Response
    {
        $start = microtime(true);
        $response = $handler($request);
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        $response->withHeader('X-Response-Time', $elapsed . 'ms');

        if ($elapsed > $this->slowThreshold) {
            \support\Log::channel('default')->warning("Slow request: {$request->method()} {$request->path()}", [
                'duration_ms' => $elapsed,
                'ip'          => $request->getRealIp(),
            ]);
        }

        return $response;
    }
}
