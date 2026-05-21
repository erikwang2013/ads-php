<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * ReplayGuard — prevents API replay attacks via nonce + timestamp.
 *
 * Clients send X-Nonce + X-Timestamp headers. Server checks:
 *   1. Timestamp within ±5 minutes of server time
 *   2. Nonce has not been used before (Redis TTL-based)
 *
 * Non-essential endpoint — best for non-browser clients (HarmonyOS, Flutter native).
 * Browser clients using JWT tokens are already protected by HTTPS + token binding.
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ReplayGuardMiddleware implements MiddlewareInterface
{
    protected int $timeWindow = 300; // ±5 minutes
    protected array $skipPaths = ['/health', '/ping', '/docs'];

    public function process(Request $request, callable $handler): Response
    {
        if (in_array($request->path(), $this->skipPaths, true)) {
            return $handler($request);
        }

        $platform = $request->clientPlatform ?? 'web';
        // Only enforce for non-browser clients (browsers use CORS + JWT binding)
        if ($platform === 'web') return $handler($request);

        $nonce = $request->header('X-Nonce', '');
        $timestamp = (int) $request->header('X-Timestamp', 0);

        if ($nonce === '' || $timestamp === 0) return $handler($request);

        // Timestamp window check
        $now = time();
        if (abs($now - $timestamp) > $this->timeWindow) {
            return $this->block('Request timestamp expired');
        }

        // Nonce uniqueness check (Redis)
        try {
            $redis = redis();
            $key = 'nonce:' . $nonce;
            if ($redis->exists($key)) {
                return $this->block('Duplicate nonce — possible replay attack');
            }
            $redis->setex($key, $this->timeWindow * 2, '1');
        } catch (\Throwable $e) {
            // Redis unavailable → allow through
        }

        return $handler($request);
    }

    protected function block(string $reason): Response
    {
        return new Response(403, ['Content-Type' => 'application/json'], json_encode([
            'code' => 403, 'message' => "Forbidden: $reason",
        ], JSON_UNESCAPED_UNICODE));
    }
}
