<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src\driver;

use OSS\OssClient;
use Webman\Http\UploadFile;
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\StorageDriver;

class AlibabaOssStorage implements StorageDriver
{
    protected ?OssClient $client = null;
    protected string $accessKeyId;
    protected string $accessKeySecret;
    protected string $bucket;
    protected string $endpoint;
    protected string $cdnDomain = '';
    protected string $cdnDriver = '';

    public function __construct(?CdnProvider $provider = null)
    {
        $this->accessKeyId = $provider?->access_key ?: (string) env('OSS_ACCESS_KEY_ID', '');
        $this->accessKeySecret = $provider?->secret_key ?: (string) env('OSS_ACCESS_KEY_SECRET', '');
        $this->bucket = $provider?->bucket ?: (string) env('OSS_BUCKET', '');
        $this->endpoint = $provider?->endpoint ?: (string) env('OSS_ENDPOINT', '');
        if ($this->endpoint === '') {
            $region = $provider?->region ?: (string) env('OSS_REGION', '');
            if ($region !== '') $this->endpoint = "https://{$region}.aliyuncs.com";
        }
        $this->cdnDomain = $provider?->cdn_domain ?: (string) env('OSS_CDN_DOMAIN', '');
        $this->cdnDriver = $provider?->cdn_driver ?: '';
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

    public function purge(array $urls): int
    {
        if ($this->cdnDriver !== 'aliyun') return 0;
        return $this->aliyunCdnRefresh($urls);
    }

    /** 阿里云 RPC 签名(canonical 串 + HMAC-SHA1),纯函数便于签名向量测试 */
    protected function aliyunCdnSignature(array $params): string
    {
        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return base64_encode(hash_hmac('sha1', 'POST&%2F&' . rawurlencode($query), $this->accessKeySecret . '&', true));
    }

    /** 阿里云 CDN RefreshObjectCaches(RPC 签名,无额外依赖) */
    protected function aliyunCdnRefresh(array $urls): int
    {
        $params = [
            'AccessKeyId'      => $this->accessKeyId,
            'Action'           => 'RefreshObjectCaches',
            'Format'           => 'JSON',
            'ObjectPath'       => implode("\n", $urls),
            'ObjectType'       => 'File',
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => bin2hex(random_bytes(8)),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'Version'          => '2018-05-10',
        ];
        $params['Signature'] = $this->aliyunCdnSignature($params);

        $ch = curl_init('https://cdn.aliyuncs.com/');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !$resp) {
            \support\Log::error("CDN purge failed: HTTP {$code}, " . substr((string) $resp, 0, 200));
            return 0;
        }
        $body = json_decode($resp, true);
        return isset($body['RefreshTaskId']) ? count($urls) : 0;
    }
}
