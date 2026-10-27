<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\service;

use Illuminate\Database\Capsule\Manager as DB;

class AuditService
{
    /**
     * Write an audit log entry.
     */
    public static function log(int $userId, string $username, string $action, string $resource, $resourceId = null, array $detail = []): void
    {
        try {
            $req = request();
            if ($req && $platform = $req->clientPlatform ?? null) {
                $detail['client_platform'] = $platform;
            }
            DB::table('admin_audit_logs')->insert([
                'id'         => snowflake_id(),
                'user_id'    => $userId,
                'username'   => $username,
                'action'     => $action,
                'resource'   => $resource,
                'resource_id'=> $resourceId !== null ? (string) $resourceId : null,
                'detail'     => !empty($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => $req ? $req->getRealIp() : '',
                'user_agent' => $req ? $req->header('User-Agent', '') : '',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging failure should never break the main flow
        }
    }
}
