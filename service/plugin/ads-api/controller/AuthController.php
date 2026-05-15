<?php
namespace plugin\ads_api\controller;

use Firebase\JWT\JWT;
use Webman\Http\Request;
use app\support\ApiResponse;

class AuthController
{
    public function login(Request $request): \Webman\Http\Response
    {
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $tenantId = (int) $request->post('tenant_id', 1);

        // Phase 1: hardcoded admin account
        if ($username !== 'admin' || $password !== 'admin123') {
            return ApiResponse::error('Invalid credentials', 1001);
        }

        $payload = [
            'uid' => 1,
            'tid' => $tenantId,
            'iat' => time(),
            'exp' => time() + (int) config('app.jwt.ttl', 86400),
        ];
        $token = JWT::encode($payload, config('app.jwt.secret'), 'HS256');

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

    public function me(Request $request): \Webman\Http\Response
    {
        return ApiResponse::success([
            'id'        => $request->userId ?? 1,
            'username'  => 'admin',
            'role'      => 'admin',
            'tenant_id' => $request->tenantId ?? 1,
        ]);
    }
}
