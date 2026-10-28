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

            // Token binding: verify IP + User-Agent
            if (!empty($payload['_ip']) && $payload['_ip'] !== $request->getRealIp()) {
                return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Token IP mismatch'], JSON_UNESCAPED_UNICODE));
            }
            $uaHash = md5($request->header('User-Agent', ''));
            if (!empty($payload['_ua']) && !hash_equals($payload['_ua'], $uaHash)) {
                return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Token UA mismatch'], JSON_UNESCAPED_UNICODE));
            }
        } catch (Throwable $e) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Token invalid or expired'], JSON_UNESCAPED_UNICODE));
        }

        // 被新登录挤出 ZSET 的 token 仍然签名有效，必须在此拒绝
        if (!SessionLimitMiddleware::isActive((int) ($payload['uid'] ?? 0), md5($header))) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Session revoked'], JSON_UNESCAPED_UNICODE));
        }

        return $handler($request);
    }
}
