<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\controller;

use Illuminate\Database\Capsule\Manager as DB;
use Webman\Http\Request;
use admin\service\AuditService;
use admin\support\HashidsService;

class AdminUserController
{
    /**
     * List users with pagination.
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);
        $keyword = $request->input('keyword', '');
        $roleId = $request->input('role_id', '');

        $query = DB::table('admin_users')
            ->leftJoin('admin_roles', 'admin_users.role_id', '=', 'admin_roles.id')
            ->select(
                'admin_users.id',
                'admin_users.username',
                'admin_users.name',
                'admin_users.email',
                'admin_users.avatar',
                'admin_users.role_id',
                'admin_users.status',
                'admin_users.last_login_at',
                'admin_users.last_login_ip',
                'admin_users.created_at',
                'admin_users.updated_at',
                'admin_roles.name as role_name',
                'admin_roles.slug as role_slug'
            );

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('admin_users.username', 'like', "%{$keyword}%")
                  ->orWhere('admin_users.name', 'like', "%{$keyword}%")
                  ->orWhere('admin_users.email', 'like', "%{$keyword}%");
            });
        }

        if ($roleId !== '') {
            $query->where('admin_users.role_id', (int) $roleId);
        }

        $total = (clone $query)->count();
        $list = $query->orderBy('admin_users.id', 'asc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($item) {
                $item->status = (int) $item->status;
                $item->role_id = (int) $item->role_id;
                $hs = new HashidsService();
                $item->id = $hs->encode($item->id);
                $item->role_id = $hs->encode((int) $item->role_id);
                return $item;
            });

        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $perPage),
                ],
            ],
        ]);
    }

    /**
     * Create a new admin user.
     */
    public function store(Request $request): \Webman\Http\Response
    {
        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $roleId = (int) $request->input('role_id', 0);

        if (!$username || !$password) {
            return json(['code' => 422, 'message' => '用户名和密码不能为空', 'data' => null]);
        }

        $exists = DB::table('admin_users')->where('username', $username)->exists();
        if ($exists) {
            return json(['code' => 422, 'message' => '用户名已存在', 'data' => null]);
        }

        $id = snowflake_id();

        DB::table('admin_users')->insert([
            'id' => $id,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'name' => $name,
            'email' => $email,
            'role_id' => $roleId,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentUser = $this->getCurrentUser($request);
        AuditService::log(
            $currentUser['id'] ?? 0,
            $currentUser['username'] ?? 'system',
            'create',
            'admin_user',
            $id,
            ['username' => $username, 'name' => $name, 'role_id' => $roleId]
        );

        return json(['code' => 0, 'message' => '创建成功', 'data' => ['id' => $id]]);
    }

    /**
     * Update an admin user.
     */
    public function update(Request $request, int $id): \Webman\Http\Response
    {
        $user = DB::table('admin_users')->find($id);
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在', 'data' => null]);
        }

        $data = [];
        $name = $request->input('name');
        if ($name !== null) {
            $data['name'] = trim($name);
        }
        $email = $request->input('email');
        if ($email !== null) {
            $data['email'] = trim($email);
        }
        $roleId = $request->input('role_id');
        if ($roleId !== null) {
            $data['role_id'] = (int) $roleId;
        }
        $status = $request->input('status');
        if ($status !== null) {
            $data['status'] = (int) $status;
        }
        $password = $request->input('password');
        if ($password) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if (empty($data)) {
            return json(['code' => 422, 'message' => '没有需要更新的字段', 'data' => null]);
        }

        $data['updated_at'] = now();

        DB::table('admin_users')->where('id', $id)->update($data);

        $currentUser = $this->getCurrentUser($request);
        AuditService::log(
            $currentUser['id'] ?? 0,
            $currentUser['username'] ?? 'system',
            'update',
            'admin_user',
            $id,
            $data
        );

        return json(['code' => 0, 'message' => '更新成功', 'data' => null]);
    }

    /**
     * Soft-delete (disable) an admin user.
     */
    public function destroy(Request $request, int $id): \Webman\Http\Response
    {
        $user = DB::table('admin_users')->find($id);
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在', 'data' => null]);
        }

        DB::table('admin_users')->where('id', $id)->update([
            'status' => 0,
            'updated_at' => now(),
        ]);

        $currentUser = $this->getCurrentUser($request);
        AuditService::log(
            $currentUser['id'] ?? 0,
            $currentUser['username'] ?? 'system',
            'delete',
            'admin_user',
            $id,
            ['username' => $user->username]
        );

        return json(['code' => 0, 'message' => '已禁用', 'data' => null]);
    }

    /**
     * List available roles.
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

    /**
     * Extract current user info from request (set by AuthCheck middleware).
     */
    private function getCurrentUser(Request $request): array
    {
        return [
            'id' => $request->adminId ?? 0,
            'username' => $request->adminUsername ?? '',
        ];
    }
}
