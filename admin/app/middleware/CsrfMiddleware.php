<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CSRF protection for admin session endpoints.
 * State-changing methods (POST/PUT/DELETE) require X-CSRF-Token header
 * matching the session-stored token.
 */

namespace admin\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    protected array $skipPaths = ['/api/admin/login', '/api/install/check', '/api/install/run', '/api/install/status'];

    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return $handler($request);
        }
        if (in_array($path, $this->skipPaths, true)) {
            return $handler($request);
        }

        $sessionToken = session('csrf_token');
        if (!$sessionToken) {
            $sessionToken = bin2hex(random_bytes(32));
            session()->set('csrf_token', $sessionToken);
        }

        $headerToken = $request->header('X-CSRF-Token', '');
        if (!hash_equals($sessionToken, $headerToken)) {
            return new Response(403, ['Content-Type' => 'application/json'], json_encode([
                'code' => 403, 'message' => 'CSRF token mismatch',
            ], JSON_UNESCAPED_UNICODE));
        }

        return $handler($request);
    }
}
