<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 定时任务引导：仅 worker 0 注册 crontab，避免多进程重复执行。
 */

namespace erik\support;

use support\Log;
use Throwable;
use Webman\Bootstrap;
use Workerman\Crontab\Crontab;
use Workerman\Worker;

class CronBootstrap implements Bootstrap
{
    public static function start(?Worker $worker)
    {
        if (!$worker || $worker->id !== 0) {
            return;
        }

        // ads-task 插件目录无 app.php，config() 不会加载 cron.php，直接 require
        $jobs = require base_path() . '/plugin/ads-task/config/cron.php';

        foreach ($jobs as $job) {
            new Crontab($job['rule'], static function () use ($job) {
                try {
                    [$class, $method] = $job['handler'];
                    (new $class())->{$method}();
                } catch (Throwable $e) {
                    Log::error('Cron job failed: ' . ($job['name'] ?? $job['rule']), ['exception' => $e]);
                }
            }, $job['name'] ?? '');
        }
    }
}
