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
            // Try session for webman-admin's built-in auth
            $adminId = session('admin.id');
            if (!$adminId) {
                return redirect('/login');
            }
            return $handler($request);
        }

        try {
            $payload = \Erikwang2013\JwtWebman\Jwt::verify($token);
            $request->adminId = $payload['uid'] ?? 0;
            $request->role = $payload['role'] ?? '';
        } catch (\Throwable $e) {
            return redirect('/login');
        }

        return $handler($request);
    }
}
