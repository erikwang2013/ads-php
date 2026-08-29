<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src\driver;

use plugin\ads_storage\model\CdnProvider;

/**
 * 腾讯云 COS — 走 S3 兼容协议(aws-sdk-php),endpoint 由 region 推导:
 * https://cos.{region}.myqcloud.com
 */
class TencentCosStorage extends S3CompatibleStorage
{
    public function __construct(?CdnProvider $provider = null)
    {
        $this->key = $provider?->access_key ?: (string) env('COS_SECRET_ID', '');
        $this->secret = $provider?->secret_key ?: (string) env('COS_SECRET_KEY', '');
        $this->bucket = $provider?->bucket ?: (string) env('COS_BUCKET', '');
        $this->region = $provider?->region ?: (string) env('COS_REGION', 'ap-guangzhou') ?: 'ap-guangzhou';
        $this->endpoint = $provider?->endpoint ?: (string) env('COS_ENDPOINT', '');
        if ($this->endpoint === '') {
            $this->endpoint = 'https://cos.' . $this->region . '.myqcloud.com';
        }
        $this->cdnDomain = $provider?->cdn_domain ?: '';
        $this->cdnDriver = $provider?->cdn_driver ?: '';
    }
}
