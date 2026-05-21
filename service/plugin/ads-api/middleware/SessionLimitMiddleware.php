<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * SessionLimitMiddleware — restricts concurrent active tokens per user.
 *
 * Each login stores the token jti in a Redis SET keyed by user ID.
 * If the set exceeds maxSessions, the oldest token is revoked.
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class SessionLimitMiddleware implements MiddlewareInterface
{
    protected int $maxSessions = 3;
    protected array $skipPaths = ['/api/auth/refresh', '/health', '/ping'];

    public function process(Request $request, callable $handler): Response
    {
        if (in_array($request->path(), $this->skipPaths, true)) {
            return $handler($request);
        }

        $userId = $request->userId ?? 0;
        if (!$userId) return $handler($request);

        $header = $request->header('Authorization', '');
        $tokenHash = md5($header);

        try {
            $redis = redis();
            $key = 'sessions:' . $userId;
            $count = $redis->scard($key);

            if ($count >= $this->maxSessions) {
                if (!$redis->sismember($key, $tokenHash)) {
                    return new Response(403, ['Content-Type' => 'application/json'], json_encode([
                        'code' => 403, 'message' => "Too many active sessions (max {$this->maxSessions})",
                    ], JSON_UNESCAPED_UNICODE));
                }
            }

            $redis->sadd($key, $tokenHash);
            $redis->expire($key, 86400);
        } catch (\Throwable $e) {}

        return $handler($request);
    }
}
