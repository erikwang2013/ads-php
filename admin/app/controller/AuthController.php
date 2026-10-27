<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\controller;

use Webman\Http\Request;

class AuthController
{
    private function adminUsername(): string
    {
        return (string) env('ADMIN_USERNAME', 'admin');
    }

    private function adminName(): string
    {
        return (string) env('ADMIN_NAME', '管理员');
    }

    /**
     * Admin login — validate env credentials and return JWT token.
     */
        /**
     * @Title("管理员登录")
     * @Group("管理后台-认证")
     * @Url("/api/admin/login")
     * @Method("POST")
     */
    public function login(Request $request): \Webman\Http\Response
    {
        // 验证码检查
        $captchaToken  = trim($request->input('captcha_token', ''));
        $captchaOffset = (int) $request->input('captcha_offset', 0);

        if (!empty($captchaToken)) {
            $captchaService = new \erik\support\CaptchaService();
            if (!$captchaService->verify($captchaToken, $captchaOffset)) {
                return json(['code' => 422, 'message' => '验证码验证失败', 'data' => null]);
            }
        }

        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');

        if (!$username || !$password) {
            return json(['code' => 422, 'message' => '用户名和密码不能为空', 'data' => null]);
        }

        $hash = (string) env('ADMIN_PASSWORD_HASH', '');
        if ($hash === '' || !hash_equals($this->adminUsername(), $username) || !password_verify($password, $hash)) {
            return json(['code' => 401, 'message' => '用户名或密码错误', 'data' => null]);
        }

        $token = \Erikwang2013\JwtWebman\Jwt::sign([
            '_ip'  => $request->getRealIp(),
            '_ua'  => md5($request->header('User-Agent', '')),
            'uid'  => 1,
            'role' => 'super_admin',
            'exp'  => time() + 86400,
        ]);

        return json([
            'code' => 0,
            'message' => '登录成功',
            'data' => [
                'access_token' => $token,
                'user' => $this->adminPayload(),
                'csrf_token' => session('csrf_token', bin2hex(random_bytes(32))),
            ],
        ]);
    }

    /**
     * Get current admin info.
     */
        /**
     * @Title("当前管理员")
     * @Group("管理后台-认证")
     * @Url("/api/admin/me")
     * @Method("GET")
     */
    public function me(Request $request): \Webman\Http\Response
    {
        $adminId = $request->adminId ?? 0;
        if (!$adminId) {
            return json(['code' => 401, 'message' => '未登录', 'data' => null]);
        }

        return json(['code' => 0, 'message' => 'ok', 'data' => $this->adminPayload()]);
    }

    /**
     * Logout — clear session.
     */
        /**
     * @Title("退出登录")
     * @Group("管理后台-认证")
     * @Url("/api/admin/logout")
     * @Method("POST")
     */
    public function logout(Request $request): \Webman\Http\Response
    {
        $request->session()->forget('admin');
        return json(['code' => 0, 'message' => '已退出', 'data' => null]);
    }

    private function adminPayload(): array
    {
        $username = $this->adminUsername();
        return [
            'id'          => 1,
            'username'    => $username,
            'name'        => $this->adminName(),
            'email'       => '',
            'avatar'      => '',
            'role_id'     => 1,
            'role'        => 'super_admin',
            'role_name'   => '超级管理员',
            'permissions' => ['*'],
        ];
    }
}
