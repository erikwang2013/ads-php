<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src;

use Webman\Http\UploadFile;

interface StorageDriver
{
    /** 连通性测试(凭据/bucket 是否可用) */
    public function test(): bool;

    /** 上传 webman 上传文件,返回公开 URL */
    public function put(string $key, UploadFile $file): string;

    /** 从本地路径上传(存量回填),返回公开 URL */
    public function putFile(string $key, string $path): string;

    public function delete(string $key): bool;

    /** 从本 driver 生成的公开 URL 反解对象并删除 */
    public function deleteUrl(string $url): bool;

    /** 预签名直传 URL(oss/s3 PUT),本地驱动不支持时抛 LogicException */
    public function signedUrl(string $key, int $expires, ?string $mime = null): string;

    public function publicUrl(string $key): string;

    /** CDN 缓存刷新,返回成功刷新数;0 = 未配置 CDN 或无缓存 */
    public function purge(array $urls): int;
}
