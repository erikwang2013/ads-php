<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 存量素材回填:把本地 uploads/assets 下的文件上传到当前默认 provider(对象存储),
 * 成功后删除本地文件。DB 中 url 保持相对路径不变,CDN 域名在读取时拼接。
 *
 * 用法:php scripts/backfill-assets.php [--dry-run]
 */

require __DIR__ . '/../vendor/autoload.php';

use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\Storage;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createUnsafeMutable(__DIR__ . '/..')->load();
}
require_once __DIR__ . '/../support/helpers.php';

$connections = require __DIR__ . '/../config/database.php';
$conn = $connections['connections'][$connections['default']];
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($conn, 'default');
$capsule->setAsGlobal();
$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher(new Illuminate\Container\Container));
$capsule->bootEloquent();

$dryRun = in_array('--dry-run', $argv, true);
$provider = CdnProvider::defaultProvider();
if (!$provider) {
    fwrite(STDERR, "No default CDN provider configured, nothing to do\n");
    exit(0);
}
if ($provider->driver === 'local') {
    fwrite(STDERR, "Default provider is local, nothing to backfill\n");
    exit(0);
}
$driver = Storage::forProvider($provider);
$base = public_path() . '/uploads/assets';

$rows = DB::table('ads_assets')->get();
$moved = 0;
$failed = 0;
foreach ($rows as $asset) {
    $rel = ltrim((string) $asset->url, '/');
    $path = $base . '/' . substr($rel, strlen('uploads/assets/'));
    if (!is_file($path)) continue;
    $key = substr($rel, strlen('uploads/assets/'));
    echo ($dryRun ? '[dry-run] ' : '') . "backfill {$asset->id} {$rel}\n";
    if ($dryRun) {
        $moved++;
        continue;
    }
    try {
        $driver->putFile($key, $path);
        unlink($path);
        $moved++;
    } catch (Throwable $e) {
        fwrite(STDERR, "failed {$asset->id}: {$e->getMessage()}\n");
        $failed++;
    }
}
echo "done: {$moved} backfilled, {$failed} failed, " . ($dryRun ? 'dry-run' : '') . "\n";
exit($failed > 0 ? 1 : 0);
