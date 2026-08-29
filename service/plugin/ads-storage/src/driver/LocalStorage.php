<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src\driver;

use Webman\Http\UploadFile;
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\StorageDriver;

class LocalStorage implements StorageDriver
{
    protected string $dir;

    public function __construct(?CdnProvider $provider = null)
    {
        $this->dir = public_path() . '/uploads/assets';
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    }

    public function test(): bool
    {
        return true;
    }

    public function put(string $key, UploadFile $file): string
    {
        $fullPath = $this->dir . '/' . $key;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file->move($fullPath);
        return $this->publicUrl($key);
    }

    public function putFile(string $key, string $path): string
    {
        $fullPath = $this->dir . '/' . $key;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        copy($path, $fullPath);
        return $this->publicUrl($key);
    }

    public function delete(string $key): bool
    {
        $path = $this->dir . '/' . $key;
        return is_file($path) && unlink($path);
    }

    public function deleteUrl(string $url): bool
    {
        $key = ltrim(substr((string) $url, strlen('/uploads/assets/')), '/');
        return $key !== '' && $this->delete($key);
    }

    public function signedUrl(string $key, int $expires, ?string $mime = null): string
    {
        throw new \LogicException('Presigned upload is not supported by local driver');
    }

    public function publicUrl(string $key): string
    {
        return '/uploads/assets/' . $key;
    }

    public function purge(array $urls): int
    {
        return count($urls); // 本地直出,无 CDN 缓存
    }
}
