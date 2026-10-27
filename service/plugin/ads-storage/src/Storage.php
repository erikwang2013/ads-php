<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src;

use Webman\Http\UploadFile;
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

    /** 按 STORAGE_DRIVER 环境变量分发,缺省 local */
    public static function driver(): StorageDriver
    {
        $name = strtolower((string) env('STORAGE_DRIVER', 'local')) ?: 'local';
        $class = self::DRIVERS[$name] ?? LocalStorage::class;
        return new $class();
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
}
