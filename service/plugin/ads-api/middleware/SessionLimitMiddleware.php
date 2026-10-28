<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * SessionLimitMiddleware — caps concurrent tokens per user and enforces revocation.
 *
 * Login/refresh register the token hash in a Redis ZSET keyed by user ID,
 * scored by issue time. Registering a fourth token evicts the oldest.
 * AuthMiddleware rejects any token no longer present in the ZSET, so evicted
 * tokens stop working rather than just disappearing from the bookkeeping.
 * A sentinel member keeps the key alive after the last token is revoked,
 * otherwise the missing-key fail-open would resurrect revoked tokens.
 * Missing ZSET keys (legacy data or 24h idle) and Redis failures fail open.
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class SessionLimitMiddleware implements MiddlewareInterface
{
    protected const MAX_SESSIONS = 3;
    protected const TTL = 86400;
    protected const SENTINEL = '__sentinel__';
    protected array $skipPaths = ['/api/v1/auth/refresh', '/health', '/ping'];

    public function process(Request $request, callable $handler): Response
    {
        if (in_array($request->path(), $this->skipPaths, true)) {
            return $handler($request);
        }

        $userId = (int) ($request->userId ?? 0);
        if (!$userId) return $handler($request);

        if (!self::isActive($userId, md5($request->header('Authorization', '')))) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode([
                'code' => 401, 'message' => 'Session revoked',
            ], JSON_UNESCAPED_UNICODE));
        }

        return $handler($request);
    }

    public static function register(int $userId, string $tokenHash): void
    {
        if ($userId <= 0) return;

        try {
            $redis = redis();
            if (!$redis) return;

            $key = 'sessions:' . $userId;
            if ($redis->zScore($key, $tokenHash) === false) {
                if ($redis->zCard($key) >= self::MAX_SESSIONS) {
                    $oldest = $redis->zRange($key, 0, 0);
                    if ($oldest) $redis->zRem($key, $oldest[0]);
                }
                $redis->zAdd($key, time(), $tokenHash);
            }
            $redis->expire($key, self::TTL);
        } catch (\Throwable $e) {}
    }

    public static function isActive(int $userId, string $tokenHash): bool
    {
        try {
            $redis = redis();
            if (!$redis) return true;

            $key = 'sessions:' . $userId;
            // 无 ZSET（升级前签发/已过期）视为未跟踪，保持兼容
            if (!$redis->exists($key)) return true;

            return $redis->zScore($key, $tokenHash) !== false;
        } catch (\Throwable $e) {
            return true;
        }
    }

    public static function revoke(int $userId, string $tokenHash): void
    {
        if ($userId <= 0) return;

        try {
            $redis = redis();
            if (!$redis) return;

            $key = 'sessions:' . $userId;
            $redis->zRem($key, $tokenHash);
            // 最后一个 token 被移除后 key 会消失，isActive() 会把缺失 key 当作未跟踪放行，
            // 导致已注销 token 复活；用哨兵占位，register() 淘汰最旧成员时会先移除它
            if ($redis->zCard($key) === 0) {
                $redis->zAdd($key, 0, self::SENTINEL);
                $redis->expire($key, self::TTL);
            }
        } catch (\Throwable $e) {}
    }
}
