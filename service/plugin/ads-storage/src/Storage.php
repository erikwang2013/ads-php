<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src;

use Webman\Http\UploadFile;
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\driver\AlibabaOssStorage;
use plugin\ads_storage\src\driver\LocalStorage;
use plugin\ads_storage\src\driver\S3CompatibleStorage;
use plugin\ads_storage\src\driver\TencentCosStorage;

class Storage
{
    protected const DRIVERS = [
        'local' => LocalStorage::class,
        'oss'   => AlibabaOssStorage::class,
        'cos'   => TencentCosStorage::class,
        's3'    => S3CompatibleStorage::class,
    ];

    /**
     * 分发:优先读 DB 默认 provider(admin 可配置),无则回退 env / local。
     * ponytail: 每次调用查库一次(单条索引查询);默认 provider 高频读取时
     * 再加 CacheService::remember('ads:cdn:default') 并随 markDefault 失效。
     */
    public static function driver(?CdnProvider $provider = null): StorageDriver
    {
        $p = $provider ?? CdnProvider::defaultProvider();
        if ($p) {
            $class = self::DRIVERS[$p->driver] ?? LocalStorage::class;
            return new $class($p);
        }
        $name = strtolower((string) env('STORAGE_DRIVER', 'local')) ?: 'local';
        $class = self::DRIVERS[$name] ?? LocalStorage::class;
        return new $class();
    }

    /** 按指定 provider 构造驱动(test/purge 等按 provider 操作时使用) */
    public static function forProvider(CdnProvider $provider): StorageDriver
    {
        return static::driver($provider);
    }

    public static function put(string $key, UploadFile $file): string
    {
        return static::driver()->put($key, $file);
    }

    public static function putFile(string $key, string $path): string
    {
        return static::driver()->putFile($key, $path);
    }

    public static function delete(string $key): bool
    {
        return static::driver()->delete($key);
    }

    public static function deleteUrl(string $url): bool
    {
        return static::driver()->deleteUrl($url);
    }

    public static function signedUrl(string $key, int $expires, ?string $mime = null): string
    {
        return static::driver()->signedUrl($key, $expires, $mime);
    }

    public static function publicUrl(string $key): string
    {
        return static::driver()->publicUrl($key);
    }

    public static function purge(array $urls): int
    {
        return static::driver()->purge($urls);
    }
}
