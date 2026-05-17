<?php
/*
 * JWT Webman Plugin - JWT authentication for webman framework
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace ErikJwt\ThinkPHP;

use Closure;
use ErikJwt\JWTException;
use think\Request;
use think\Response;

class Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $jwt    = app('erik.jwt');
        $config = config('jwt');

        $except = $config['middleware']['except'] ?? [];
        $path   = $request->pathinfo();
        foreach ($except as $pattern) {
            if (preg_match('#^' . $pattern . '$#', $path)) {
                return $next($request);
            }
        }

        $token = $request->header('Authorization', '');
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        if (empty($token)) {
            return json(['code' => 401, 'msg' => 'Token not provided', 'data' => null])->code(401);
        }

        try {
            $payload = $jwt->decode($token);
            $request->jwt_payload = $payload;
        } catch (JWTException $e) {
            return json(['code' => 401, 'msg' => $e->getMessage(), 'data' => null])->code(401);
        }

        return $next($request);
    }
}
