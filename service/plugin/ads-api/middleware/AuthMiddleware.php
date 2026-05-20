<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\middleware;

use erik\support\JwtService as Jwt;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Throwable;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
        }

        $token = substr($header, 7);
        try {
            $payload = Jwt::verify($token);
            $request->userId = $payload['uid'];
            $request->tenantId = $payload['tid'] ?? 1;
        } catch (Throwable $e) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Token invalid or expired'], JSON_UNESCAPED_UNICODE));
        }

        return $handler($request);
    }
}
