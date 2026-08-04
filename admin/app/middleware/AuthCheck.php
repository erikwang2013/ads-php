<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization', ''));
        if (!$token) {
            $adminId = session('admin.id');
            if (!$adminId) {
                return redirect('/login');
            }
            return $handler($request);
        }

        try {
            $payload = \Erikwang2013\JwtWebman\Jwt::verify($token);

            // Token binding: verify IP + User-Agent
            if (!empty($payload['_ip']) && $payload['_ip'] !== $request->getRealIp()) {
                return redirect('/login');
            }
            $uaHash = md5($request->header('User-Agent', ''));
            if (!empty($payload['_ua']) && !hash_equals($payload['_ua'], $uaHash)) {
                return redirect('/login');
            }

            $request->adminId = $payload['uid'] ?? 0;
            $request->role = $payload['role'] ?? '';
        } catch (\Throwable $e) {
            return redirect('/login');
        }

        return $handler($request);
    }
}
