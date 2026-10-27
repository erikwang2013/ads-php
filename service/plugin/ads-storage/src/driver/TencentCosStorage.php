<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\src\driver;

/**
 * 腾讯云 COS — 走 S3 兼容协议(aws-sdk-php),endpoint 由 region 推导:
 * https://cos.{region}.myqcloud.com
 */
class TencentCosStorage extends S3CompatibleStorage
{
    public function __construct()
    {
        $this->key = (string) env('COS_SECRET_ID', '');
        $this->secret = (string) env('COS_SECRET_KEY', '');
        $this->bucket = (string) env('COS_BUCKET', '');
        $this->region = (string) env('COS_REGION', 'ap-guangzhou') ?: 'ap-guangzhou';
        $this->endpoint = (string) env('COS_ENDPOINT', '');
        if ($this->endpoint === '') {
            $this->endpoint = 'https://cos.' . $this->region . '.myqcloud.com';
        }
    }
}
