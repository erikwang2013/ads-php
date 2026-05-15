<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_tenant\middleware;

use plugin\ads_tenant\model\Tenant;
use plugin\ads_tenant\config\Database;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class TenantIdentify implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $tenantId = $request->header('X-Tenant-Id')
            ?? $request->sessionGet('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->isActive()) {
                Database::connect($tenant);
                // 全局绑定
                app()->instance('current_tenant', $tenant);
                app()->instance('current_connection', Database::connectionName($tenant));
            }
        }

        return $handler($request);
    }
}
