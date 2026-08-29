<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 平台管理员守卫:仅 tenant 1(平台主租户)可访问平台级管理接口。
 * 项目暂无角色体系,登录即 admin_users,其余接口按各自 tenantId 隔离;
 * 平台级全局配置(如 CDN 服务商)不能由任意租户管理员接管。
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AdminMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if (((int) ($request->tenantId ?? 0)) !== 1) {
            return new Response(403, ['Content-Type' => 'application/json'],
                json_encode(['code' => 403, 'message' => 'Forbidden: platform admin only'], JSON_UNESCAPED_UNICODE));
        }
        return $handler($request);
    }
}
