<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 定时任务配置存在性：bootstraps 引用 CronBootstrap，cron.php 定义 3 个作业。
 * 不在测试中实例化 Crontab（会注册真实定时器）。
 */

namespace Tests\Unit;

use erik\support\CronBootstrap;
use Tests\TestCase;
use Webman\Bootstrap;

class CronBootstrapTest extends TestCase
{
    public function testBootstrapConfigReferencesCronBootstrap(): void
    {
        $bootstraps = require __DIR__ . '/../../config/bootstrap.php';

        $this->assertContains(CronBootstrap::class, $bootstraps);
        $this->assertTrue(is_subclass_of(CronBootstrap::class, Bootstrap::class));
    }

    public function testCronConfigDefinesThreeJobs(): void
    {
        $jobs = require __DIR__ . '/../../plugin/ads-task/config/cron.php';

        $this->assertCount(3, $jobs);
        $this->assertSame(
            ['TokenRefresh', 'DataSync', 'RetrySync'],
            array_column($jobs, 'name')
        );
        foreach ($jobs as $job) {
            $this->assertNotEmpty($job['rule']);
            $this->assertIsArray($job['handler']);
            $this->assertCount(2, $job['handler']);
        }
    }
}
