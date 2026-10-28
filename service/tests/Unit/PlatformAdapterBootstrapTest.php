<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 生产环境适配器注册：插件目录无 app.php，webman 不会加载其 config，
 * 由 config/autoload.php 的 files 手动 include。
 */

namespace Tests\Unit;

use plugin\ads_platform\src\AdapterRegistry;
use Tests\TestCase;

class PlatformAdapterBootstrapTest extends TestCase
{
    public function testAutoloadConfigIncludesPlatformBootstrapAfterHelpers(): void
    {
        $files = (require __DIR__ . '/../../config/autoload.php')['files'];

        $this->assertSame(base_path() . '/support/helpers.php', $files[0]);
        $this->assertContains(base_path() . '/plugin/ads-platform/config/bootstrap.php', $files);
    }

    public function testBootstrapRegistersAllPlatformAdapters(): void
    {
        require_once __DIR__ . '/../../plugin/ads-platform/config/bootstrap.php';

        $this->assertTrue(AdapterRegistry::has('juliang'));
        $this->assertGreaterThanOrEqual(29, count(AdapterRegistry::all()));
    }
}
