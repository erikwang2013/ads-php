<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\controller;

use Illuminate\Database\Capsule\Manager as DB;
use Webman\Http\Request;
use admin\service\AuditService;

class AuthController
{
    /**
     * Admin login — validate credentials and return JWT token.
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

        $user = DB::table('admin_users')->where('username', $username)->first();

        if (!$user || !password_verify($password, $user->password)) {
            return json(['code' => 401, 'message' => '用户名或密码错误', 'data' => null]);
        }

        if ((int) $user->status !== 1) {
            return json(['code' => 403, 'message' => '账户已被禁用', 'data' => null]);
        }

        $role = DB::table('admin_roles')->find($user->role_id);
        $permissions = [];
        if ($role && $role->permissions) {
            $permissions = json_decode($role->permissions, true) ?: [];
        }

        $token = \Erikwang2013\JwtWebman\Jwt::sign([
            'uid'  => $user->id,
            'role' => $role->slug ?? '',
            'exp'  => time() + 86400,
        ]);

        // Update last login info
        DB::table('admin_users')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->getRealIp(),
            'updated_at'    => now(),
        ]);

        AuditService::log(
            $user->id,
            $user->username,
            'login',
            'auth',
            $user->id,
            ['ip' => $request->getRealIp()]
        );

        return json([
            'code' => 0,
            'message' => '登录成功',
            'data' => [
                'access_token' => $token,
                'user' => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'avatar'   => $user->avatar,
                    'role_id'  => (int) $user->role_id,
                    'role'     => $role->slug ?? '',
                    'role_name'=> $role->name ?? '',
                    'permissions' => $permissions,
                ],
                'csrf_token' => session('csrf_token', bin2hex(random_bytes(32))),
            ],
        ]);
    }

    /**
     * Get current user info with roles and permissions.
     */
    public function me(Request $request): \Webman\Http\Response
    {
        $adminId = $request->adminId ?? 0;
        if (!$adminId) {
            return json(['code' => 401, 'message' => '未登录', 'data' => null]);
        }

        $user = DB::table('admin_users')->find($adminId);
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在', 'data' => null]);
        }

        $role = DB::table('admin_roles')->find($user->role_id);
        $permissions = [];
        if ($role && $role->permissions) {
            $permissions = json_decode($role->permissions, true) ?: [];
        }

        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'id'       => $user->id,
                'username' => $user->username,
                'name'     => $user->name,
                'email'    => $user->email,
                'avatar'   => $user->avatar,
                'role_id'  => (int) $user->role_id,
                'role'     => $role->slug ?? '',
                'role_name'=> $role->name ?? '',
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Logout — clear session.
     */
    public function logout(Request $request): \Webman\Http\Response
    {
        $adminId = $request->adminId ?? 0;
        if ($adminId) {
            AuditService::log(
                $adminId,
                $request->adminUsername ?? '',
                'logout',
                'auth',
                $adminId
            );
        }

        $request->session()->forget('admin');

        return json(['code' => 0, 'message' => '已退出', 'data' => null]);
    }

    /**
     * List available roles (public endpoint for login page).
     */
    public function roles(): \Webman\Http\Response
    {
        $roles = DB::table('admin_roles')->get()->map(function ($item) {
            $item->permissions = json_decode($item->permissions, true);
            return $item;
        });

        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => $roles,
        ]);
    }
}
