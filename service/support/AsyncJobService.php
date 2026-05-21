<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * AsyncJobService — pushes long-running tasks to Redis queue for async processing.
 *
 * Uses webman/redis-queue (already in composer.json).
 * Decouples HTTP request from heavy operations like data sync, report generation.
 *
 * Usage:
 *   AsyncJobService::dispatch('sync', ['account_id' => 123]);
 */

namespace erik\support;

class AsyncJobService
{
    protected const QUEUE_KEY = 'queue:async:';
    protected static array $queues = ['sync', 'report', 'export', 'notification'];

    public static function dispatch(string $queue, array $payload): bool
    {
        if (!in_array($queue, self::$queues, true)) return false;

        $data = json_encode([
            'queue'     => $queue,
            'payload'   => $payload,
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE);

        try {
            $redis = redis();
            $redis->rPush(self::QUEUE_KEY . $queue, $data);
            return true;
        } catch (\Throwable $e) {
            \support\Log::channel('default')->warning("AsyncJob dispatch failed: {$e->getMessage()}");
            return false;
        }
    }

    public static function processQueue(string $queue, callable $handler, int $timeout = 30): int
    {
        $processed = 0;
        $deadline = time() + $timeout;

        try {
            $redis = redis();
            while (time() < $deadline) {
                $data = $redis->lPop(self::QUEUE_KEY . $queue);
                if (!$data) break;

                $job = json_decode($data, true);
                if (!$job) continue;

                try {
                    $handler($job['payload'] ?? []);
                    $processed++;
                } catch (\Throwable $e) {
                    \support\Log::channel('default')->error("AsyncJob failed: {$e->getMessage()}", $job);
                }
            }
        } catch (\Throwable $e) {}

        return $processed;
    }

    public static function pending(string $queue): int
    {
        try { return (int) redis()->lLen(self::QUEUE_KEY . $queue); }
        catch (\Throwable $e) { return 0; }
    }
}
