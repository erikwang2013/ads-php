<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use erik\support\JwtService as Jwt;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;

class AuthController
{
    public function login(Request $request): \Webman\Http\Response
    {
        $captchaToken  = $request->post('captcha_token', '');
        $captchaOffset = (int) $request->post('captcha_offset', 0);

        if (!empty($captchaToken)) {
            $captchaService = new \erik\support\CaptchaService();
            if (!$captchaService->verify($captchaToken, $captchaOffset)) {
                return ApiResponse::error('验证码验证失败');
            }
        }

        $username = trim($request->post('username', ''));
        $password = $request->post('password', '');
        $tenantId = (int) $request->post('tenant_id', 1);

        if ($username === '' || $password === '') {
            return ApiResponse::error('用户名和密码不能为空', 1001);
        }

        $user = DB::table('admin_users')
            ->where('username', $username)
            ->where('status', 1)
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
            return ApiResponse::error('用户名或密码错误', 1001);
        }

        $token = Jwt::encode([
            'uid' => $user->id,
            'tid' => $tenantId,
        ]);

        return ApiResponse::success([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => (int) config('app.jwt.ttl', 86400),
            'user'         => [
                'id'       => $user->id,
                'username' => $user->username,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => 'admin',
            ],
        ]);
    }

    public function me(Request $request): \Webman\Http\Response
    {
        $uid = $request->userId ?? 0;
        if (!$uid) {
            return ApiResponse::error('未登录', 401, 401);
        }

        $user = DB::table('admin_users')->find($uid);
        if (!$user) {
            return ApiResponse::error('用户不存在', 404);
        }

        return ApiResponse::success([
            'id'        => $user->id,
            'username'  => $user->username,
            'name'      => $user->name,
            'email'     => $user->email,
            'role'      => 'admin',
            'tenant_id' => $request->tenantId ?? 1,
        ]);
    }

    public function refreshToken(Request $request): \Webman\Http\Response
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return ApiResponse::error('Token 未提供', 401, 401);
        }
        $token = substr($header, 7);

        try {
            $newToken = Jwt::refresh($token);
            return ApiResponse::success([
                'access_token' => $newToken,
                'token_type'   => 'Bearer',
                'expires_in'   => (int) config('app.jwt.ttl', 86400),
            ], 'Token 已刷新');
        } catch (\Erikwang2013\Jwt\JWTException $e) {
            return ApiResponse::error($e->getMessage(), 401, 401);
        }
    }
}
