<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src\driver;

use OSS\OssClient;
use Webman\Http\UploadFile;
use plugin\ads_storage\src\StorageDriver;

class AlibabaOssStorage implements StorageDriver
{
    protected ?OssClient $client = null;
    protected string $accessKeyId;
    protected string $accessKeySecret;
    protected string $bucket;
    protected string $endpoint;
    protected string $cdnDomain = '';

    public function __construct()
    {
        $this->accessKeyId = (string) env('OSS_ACCESS_KEY_ID', '');
        $this->accessKeySecret = (string) env('OSS_ACCESS_KEY_SECRET', '');
        $this->bucket = (string) env('OSS_BUCKET', '');
        $this->endpoint = (string) env('OSS_ENDPOINT', '');
        if ($this->endpoint === '') {
            $region = (string) env('OSS_REGION', '');
            if ($region !== '') $this->endpoint = "https://{$region}.aliyuncs.com";
        }
        $this->cdnDomain = (string) env('OSS_CDN_DOMAIN', '');
    }

    protected function client(): OssClient
    {
        if (!$this->client) {
            $this->client = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
        }
        return $this->client;
    }

    protected function object(string $key): string
    {
        return 'uploads/assets/' . ltrim($key, '/');
    }

    public function test(): bool
    {
        return $this->client()->doesBucketExist($this->bucket);
    }

    public function put(string $key, UploadFile $file): string
    {
        $this->client()->uploadFile($this->bucket, $this->object($key), $file->getRealPath());
        return $this->publicUrl($key);
    }

    public function putFile(string $key, string $path): string
    {
        $this->client()->uploadFile($this->bucket, $this->object($key), $path);
        return $this->publicUrl($key);
    }

    public function delete(string $key): bool
    {
        try {
            $this->client()->deleteObject($this->bucket, $this->object($key));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteUrl(string $url): bool
    {
        $object = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($object === '' || !str_starts_with($object, 'uploads/assets/')) return false;
        try {
            $this->client()->deleteObject($this->bucket, $object);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function signedUrl(string $key, int $expires, ?string $mime = null): string
    {
        $options = $mime ? ['Content-Type' => $mime] : null;
        return $this->client()->signUrl($this->bucket, $this->object($key), $expires, 'PUT', $options);
    }

    public function publicUrl(string $key): string
    {
        $path = $this->object($key);
        if ($this->cdnDomain !== '') {
            return 'https://' . rtrim($this->cdnDomain, '/') . '/' . $path;
        }
        $host = (string) parse_url($this->endpoint, PHP_URL_HOST);
        if ($port = parse_url($this->endpoint, PHP_URL_PORT)) $host .= ':' . $port;
        return "https://{$this->bucket}.{$host}/{$path}";
    }
}
