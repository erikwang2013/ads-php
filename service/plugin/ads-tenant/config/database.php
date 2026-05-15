<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_tenant\config;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_tenant\model\Tenant;

class Database
{
    public static function connect(Tenant $tenant): void
    {
        if ($tenant->db_type === 'shared') {
            return;
        }
        $cfg = $tenant->db_config;
        $name = 'tenant_' . $tenant->id;
        $config = DB::getDatabaseManager()->getConfig('shared');
        $config['database'] = $cfg['database'] ?? $name;
        $config['host']     = $cfg['host']     ?? $config['host'];
        $config['username'] = $cfg['username'] ?? $config['username'];
        $config['password'] = $cfg['password'] ?? $config['password'];
        DB::getDatabaseManager()->setConfig($name, $config);
        DB::connection($name);
    }

    public static function connectionName(Tenant $tenant): string
    {
        return $tenant->db_type === 'dedicated' ? 'tenant_' . $tenant->id : 'shared';
    }
}
