<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * OriginGuard — validates Origin/Referer for API requests.
 * On same-origin requests, Origin may be absent → allowed.
 * On cross-origin requests, Origin must match whitelist or request is blocked.
 * Rejects non-standard HTTP methods (TRACE/DEBUG/CONNECT/TRACK).
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class OriginGuardMiddleware implements MiddlewareInterface
{
    // Non-standard / dangerous HTTP methods to reject
    protected const BLOCKED_METHODS = ['TRACE', 'DEBUG', 'CONNECT', 'TRACK', 'OPTIONS-POST', 'OPTIONS-PUT'];

    protected function getAllowedOrigins(): array
    {
        $origins = env('ALLOWED_ORIGINS', env('APP_URL', 'http://127.0.0.1:8788'));
        return array_map('trim', explode(',', $origins));
    }

    public function process(Request $request, callable $handler): Response
    {
        // 1. Reject non-standard HTTP methods
        if (in_array(strtoupper($request->method()), self::BLOCKED_METHODS, true)) {
            return $this->block("HTTP method {$request->method()} is not allowed");
        }

        // 2. Origin/Referer check for state-changing requests
        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return $handler($request);
        }

        $origin = $request->header('Origin', $request->header('Referer', ''));
        if ($origin === '') return $handler($request);

        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $host = parse_url($origin, PHP_URL_HOST) ?: '';
        if ($host === '') return $handler($request);

        foreach ($this->getAllowedOrigins() as $pattern) {
            $pHost = parse_url($pattern, PHP_URL_HOST) ?: $pattern;
            if ($host === $pHost || fnmatch($pHost, $host)) return $handler($request);
        }

        return $this->block("Origin '$origin' is not allowed");
    }

    protected function block(string $reason): Response
    {
        return new Response(403, ['Content-Type' => 'application/json'], json_encode([
            'code' => 403, 'message' => "Forbidden: $reason",
        ], JSON_UNESCAPED_UNICODE));
    }
}
