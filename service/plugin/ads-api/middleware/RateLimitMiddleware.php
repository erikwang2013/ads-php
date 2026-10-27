<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    protected int $maxRequests;
    protected int $windowSeconds;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function process(Request $request, callable $handler): Response
    {
        $key = 'rate_limit:' . ($request->userId ?? $request->getRealIp());
        $redis = redis();

        if (!$redis) {
            return $handler($request);
        }

        $now = time();
        $windowStart = $now - $this->windowSeconds;

        // Remove old entries
        $redis->zRemRangeByScore($key, 0, $windowStart);
        // Count current window
        $count = $redis->zCard($key);

        if ($count >= $this->maxRequests) {
            return new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => $this->windowSeconds,
            ], json_encode([
                'code' => 429,
                'message' => 'Too Many Requests. Try again in ' . $this->windowSeconds . ' seconds.',
            ], JSON_UNESCAPED_UNICODE));
        }

        $redis->zAdd($key, $now, $now . '.' . uniqid());
        $redis->expire($key, $this->windowSeconds);

        return $handler($request);
    }
}
