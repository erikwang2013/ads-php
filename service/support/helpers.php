<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Global helper functions loaded via config/autoload.php.
 */

if (!function_exists('redis')) {
    /**
     * Get the shared Redis client (config/redis.php default connection).
     */
    function redis(): \Redis
    {
        static $client = null;

        if ($client instanceof \Redis && $client->ping() !== false) {
            return $client;
        }

        $config = config('redis.default');
        $client = new \Redis();
        $client->connect(
            $config['host'] ?? '127.0.0.1',
            (int) ($config['port'] ?? 6379),
            (float) ($config['connection_timeout'] ?? 3)
        );
        if (!empty($config['password'])) {
            $client->auth($config['password']);
        }
        $client->select((int) ($config['database'] ?? 0));
        return $client;
    }
}
