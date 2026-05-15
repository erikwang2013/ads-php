<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller;

use Erikwang2013\JwtWebman\Jwt;
use Webman\Http\Request;
use app\support\ApiResponse;

class AuthController
{
    public function login(Request $request): Webman\Http\Response
    {
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $tenantId = (int) $request->post('tenant_id', 1);

        // Phase 1: hardcoded admin account
        if ($username !== 'admin' || $password !== 'admin123') {
            return ApiResponse::error('Invalid credentials', 1001);
        }

        $token = Jwt::encode([
            'uid' => 1,
            'tid' => $tenantId,
        ]);

        return ApiResponse::success([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => (int) config('app.jwt.ttl', 86400),
            'user'         => [
                'id'       => 1,
                'username' => $username,
                'role'     => 'admin',
            ],
        ]);
    }

    public function me(Request $request): Webman\Http\Response
    {
        return ApiResponse::success([
            'id'        => $request->userId ?? 1,
            'username'  => 'admin',
            'role'      => 'admin',
            'tenant_id' => $request->tenantId ?? 1,
        ]);
    }
}
