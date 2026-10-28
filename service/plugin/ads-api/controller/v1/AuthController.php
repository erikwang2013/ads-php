<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use erik\support\JwtService as Jwt;
use plugin\ads_api\middleware\SessionLimitMiddleware;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;

class AuthController
{
        /**
     * @Title("登录")
     * @Group("认证")
     * @Url("/api/v1/auth/login")
     * @Method("POST")
     */
    public function login(Request $request): \Webman\Http\Response
    {
        if (env('CAPTCHA_ENABLED', true) !== false) {
            $captchaToken = (string) $request->post('captcha_token', '');
            if ($captchaToken === '') {
                return ApiResponse::error('验证码不能为空');
            }
            try {
                $captchaService = new \erik\support\CaptchaService();
                if (!$captchaService->verify($captchaToken, (int) $request->post('captcha_offset', 0))) {
                    return ApiResponse::error('验证码验证失败');
                }
            } catch (\Throwable $e) {
                return ApiResponse::error('验证码验证失败');
            }
        }

        $username = trim($request->post('username', ''));
        $password = $request->post('password', '');

        if ($username === '' || $password === '') {
            return ApiResponse::error('用户名和密码不能为空', 1001);
        }

        $hash = (string) env('API_PASSWORD_HASH', '');
        if ($hash === '' || !hash_equals((string) env('API_USERNAME', 'admin'), $username) || !password_verify($password, $hash)) {
            return ApiResponse::error('用户名或密码错误', 1001);
        }

        $token = Jwt::encode(['uid' => 1]);
        SessionLimitMiddleware::register(1, md5('Bearer ' . $token));

        return ApiResponse::success([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => (int) config('app.jwt.ttl', 86400),
            'user'         => [
                'id'       => 1,
                'username' => (string) env('API_USERNAME', 'admin'),
                'name'     => (string) env('API_NAME', '管理员'),
                'email'    => '',
                'role'     => 'admin',
            ],
        ]);
    }

        /**
     * @Title("当前用户")
     * @Group("认证")
     * @Url("/api/v1/auth/me")
     * @Method("GET")
     */
    public function me(Request $request): \Webman\Http\Response
    {
        $uid = $request->userId ?? 0;
        if (!$uid) {
            return ApiResponse::error('未登录', 401, 401);
        }

        return ApiResponse::success([
            'id'        => $uid,
            'username'  => (string) env('API_USERNAME', 'admin'),
            'name'      => (string) env('API_NAME', '管理员'),
            'email'     => '',
            'role'      => 'admin',
        ]);
    }

        /**
     * @Title("刷新Token")
     * @Group("认证")
     * @Url("/api/v1/auth/refresh")
     * @Method("POST")
     */
    public function refreshToken(Request $request): \Webman\Http\Response
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return ApiResponse::error('Token 未提供', 401, 401);
        }
        $token = substr($header, 7);
        $expiresIn = (int) env('JWT_TTL', 86400);

        try {
            $newToken = Jwt::refresh($token);
            $userId = (int) ($request->userId ?? 0);
            if ($userId) {
                SessionLimitMiddleware::revoke($userId, md5($header));
                SessionLimitMiddleware::register($userId, md5('Bearer ' . $newToken));
            }
            return ApiResponse::success([
                'access_token' => $newToken,
                'token_type'   => 'Bearer',
                'expires_in'   => $expiresIn,
            ], 'Token 已刷新');
        } catch (\Erikwang2013\Jwt\JWTException $e) {
            return ApiResponse::error($e->getMessage(), 401, 401);
        }
    }

        /**
     * @Title("退出登录")
     * @Group("认证")
     * @Url("/api/v1/auth/logout")
     * @Method("POST")
     */
    public function logout(Request $request): \Webman\Http\Response
    {
        $header = $request->header('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';

        try {
            if ($token !== '') Jwt::blacklist($token);
        } catch (\Throwable $e) {
            // 黑名单写入失败不阻断退出，ZSET 侧仍会撤销该会话
            \support\Log::error('logout: token blacklist failed', ['exception' => $e]);
        }

        SessionLimitMiddleware::revoke((int) ($request->userId ?? 0), md5($header));

        return ApiResponse::success(null, '已退出');
    }
}
