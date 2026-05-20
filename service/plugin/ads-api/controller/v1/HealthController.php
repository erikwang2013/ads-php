<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use Webman\Http\Response;

class HealthController
{
    public function health(): Response
    {
        // Database check
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo()->query('SELECT 1');
            $dbStatus = 'ok';
        } catch (\Throwable $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        // Redis check
        try {
            $redis = redis();
            if ($redis instanceof \Redis) {
                $redis->ping();
                $redisStatus = 'ok';
            } else {
                $redisStatus = 'unavailable';
            }
        } catch (\Throwable $e) {
            $redisStatus = 'error: ' . $e->getMessage();
        }

        $healthy = ($dbStatus === 'ok' && $redisStatus === 'ok');
        $statusCode = $healthy ? 200 : 503;

        return new Response($statusCode, ['Content-Type' => 'application/json'], json_encode([
            'status'    => $healthy ? 'healthy' : 'degraded',
            'timestamp' => date('c'),
            'checks'    => [
                'database' => $dbStatus,
                'redis'    => $redisStatus,
            ],
        ], JSON_UNESCAPED_UNICODE));
    }

    public function ping(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['pong' => true]));
    }
}
