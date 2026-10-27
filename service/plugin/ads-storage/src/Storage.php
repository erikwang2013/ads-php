<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src;

use Webman\Http\UploadFile;
use plugin\ads_storage\src\driver\LocalStorage;

class Storage
{
    public static function driver(): StorageDriver
    {
        return new LocalStorage();
    }

    public static function put(string $key, UploadFile $file): string
    {
        return static::driver()->put($key, $file);
    }

    public static function delete(string $key): bool
    {
        return static::driver()->delete($key);
    }

    public static function deleteUrl(string $url): bool
    {
        return static::driver()->deleteUrl($url);
    }

    public static function publicUrl(string $key): string
    {
        return static::driver()->publicUrl($key);
    }
}
