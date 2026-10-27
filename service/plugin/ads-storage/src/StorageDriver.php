<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src;

use Webman\Http\UploadFile;

interface StorageDriver
{
    /** 连通性测试 */
    public function test(): bool;

    /** 上传 webman 上传文件,返回公开 URL */
    public function put(string $key, UploadFile $file): string;

    public function delete(string $key): bool;

    /** 从公开 URL 反解路径并删除 */
    public function deleteUrl(string $url): bool;

    public function publicUrl(string $key): string;
}
