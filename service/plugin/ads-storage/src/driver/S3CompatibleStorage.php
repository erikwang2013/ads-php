<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src\driver;

use Aws\S3\S3Client;
use Webman\Http\UploadFile;
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\StorageDriver;

/**
 * S3 兼容对象存储(aws-sdk-php)。
 * 覆盖:S3、Cloudflare R2(自定义 endpoint)、MinIO 等。
 * COS 走 S3 兼容协议,见 TencentCosStorage。
 */
class S3CompatibleStorage implements StorageDriver
{
    protected ?S3Client $client = null;
    protected string $key;
    protected string $secret;
    protected string $bucket;
    protected string $region = 'auto';
    protected string $endpoint = '';
    protected string $cdnDomain = '';
    protected string $cdnDriver = '';

    public function __construct(?CdnProvider $provider = null)
    {
        $this->key = $provider?->access_key ?: (string) env('S3_ACCESS_KEY', '');
        $this->secret = $provider?->secret_key ?: (string) env('S3_SECRET_KEY', '');
        $this->bucket = $provider?->bucket ?: (string) env('S3_BUCKET', '');
        $this->region = $provider?->region ?: (string) env('S3_REGION', 'auto') ?: 'auto';
        $this->endpoint = $provider?->endpoint ?: (string) env('S3_ENDPOINT', '');
        $this->cdnDomain = $provider?->cdn_domain ?: (string) env('S3_CDN_DOMAIN', '');
        $this->cdnDriver = $provider?->cdn_driver ?: '';
    }

    protected function client(): S3Client
    {
        if (!$this->client) {
            $config = [
                'version'     => 'latest',
                'region'      => $this->region ?: 'auto',
                'credentials' => ['key' => $this->key, 'secret' => $this->secret],
            ];
            if ($this->endpoint !== '') {
                $config['endpoint'] = $this->endpoint;
                $config['use_path_style_endpoint'] = true;
            }
            $this->client = new S3Client($config);
        }
        return $this->client;
    }

    protected function object(string $key): string
    {
        return 'uploads/assets/' . ltrim($key, '/');
    }

    public function test(): bool
    {
        try {
            $this->client()->headBucket(['Bucket' => $this->bucket]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function put(string $key, UploadFile $file): string
    {
        $this->client()->putObject([
            'Bucket'     => $this->bucket,
            'Key'        => $this->object($key),
            'SourceFile' => $file->getRealPath(),
        ]);
        return $this->publicUrl($key);
    }

    public function putFile(string $key, string $path): string
    {
        $this->client()->putObject([
            'Bucket'     => $this->bucket,
            'Key'        => $this->object($key),
            'SourceFile' => $path,
        ]);
        return $this->publicUrl($key);
    }

    public function delete(string $key): bool
    {
        return $this->deleteObject($this->object($key));
    }

    public function deleteUrl(string $url): bool
    {
        $object = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($object === '' || !str_starts_with($object, 'uploads/assets/')) return false;
        return $this->deleteObject($object);
    }

    protected function deleteObject(string $object): bool
    {
        try {
            $this->client()->deleteObject(['Bucket' => $this->bucket, 'Key' => $object]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function signedUrl(string $key, int $expires, ?string $mime = null): string
    {
        $args = ['Bucket' => $this->bucket, 'Key' => $this->object($key)];
        if ($mime) $args['ContentType'] = $mime;
        $cmd = $this->client()->getCommand('PutObject', $args);
        $req = $this->client()->createPresignedRequest($cmd, '+' . $expires . ' seconds');
        return (string) $req->getUri();
    }

    public function publicUrl(string $key): string
    {
        $path = $this->object($key);
        if ($this->cdnDomain !== '') {
            return 'https://' . rtrim($this->cdnDomain, '/') . '/' . $path;
        }
        if ($this->endpoint !== '') {
            return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $path;
        }
        // region=auto(R2 约定)无 endpoint 时按 AWS 默认域处理,避免生成 s3.auto.amazonaws.com 无效域名
        $host = in_array($this->region, ['auto', 'us-east-1'], true) ? 's3.amazonaws.com' : "s3.{$this->region}.amazonaws.com";
        return "https://{$this->bucket}.{$host}/{$path}";
    }

    public function purge(array $urls): int
    {
        // 各 CDN 厂商刷新接口差异大(cloudflare 需 zone id 等),现阶段仅 aliyun 支持
        return 0;
    }
}
