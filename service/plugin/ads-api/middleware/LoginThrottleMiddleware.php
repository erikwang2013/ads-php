<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * LoginThrottleMiddleware — brute-force protection via Redis.
 * 5 failed attempts per username → 15 minute lockout.
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class LoginThrottleMiddleware implements MiddlewareInterface
{
    protected int $maxAttempts = 5;
    protected int $lockoutSeconds = 900;

    public function process(Request $request, callable $handler): Response
    {
        if ($request->path() !== '/api/auth/login' || $request->method() !== 'POST') {
            return $handler($request);
        }

        $username = $request->post('username', '');
        if ($username === '') return $handler($request);

        $key = 'login_throttle:' . md5($username);

        try {
            $redis = redis();
            $attempts = (int) $redis->get($key);

            if ($attempts >= $this->maxAttempts) {
                $ttl = $redis->ttl($key);
                $remaining = max(0, $ttl);
                return new Response(429, ['Content-Type' => 'application/json', 'Retry-After' => (string) $remaining], json_encode([
                    'code' => 429, 'message' => "Too many login attempts. Retry after {$remaining}s",
                ], JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            return $handler($request);
        }

        $response = $handler($request);
        $body = json_decode($response->rawBody(), true);

        if (($body['code'] ?? 0) !== 0) {
            try {
                $redis = redis();
                $count = $redis->incr($key);
                if ($count === 1) $redis->expire($key, $this->lockoutSeconds);
            } catch (\Throwable $e) {}
        } else {
            try { redis()->del($key); } catch (\Throwable $e) {}
        }

        return $response;
    }
}
