<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 阿里云 RPC 签名向量测试。输入取自阿里云 RefreshObjectCaches 文档签名示例
 * (AccessKeyId=testid, AccessKeySecret=testsecret),期望值为 PHP 独立重算结果,
 * 防止签名串拼接/编码被无意改动。
 */

namespace Tests\Unit;

use plugin\ads_storage\src\driver\AlibabaOssStorage;

class AlibabaCdnSignatureTest extends \Tests\TestCase
{
    public function testRpcSignatureMatchesDocumentedVector(): void
    {
        $storage = new AlibabaOssStorage();

        $secret = new \ReflectionProperty(AlibabaOssStorage::class, 'accessKeySecret');
        $secret->setAccessible(true);
        $secret->setValue($storage, 'testsecret');

        $method = new \ReflectionMethod(AlibabaOssStorage::class, 'aliyunCdnSignature');
        $method->setAccessible(true);

        $params = [
            'AccessKeyId'      => 'testid',
            'Action'           => 'RefreshObjectCaches',
            'Format'           => 'JSON',
            'ObjectPath'       => "http://example.com/pic1.jpg\nhttp://example.com/pic2.jpg",
            'ObjectType'       => 'File',
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => '550e8400e29b41d4a716446655440000',
            'SignatureVersion' => '1.0',
            'Timestamp'        => '2026-08-29T12:00:00Z',
            'Version'          => '2018-05-10',
        ];
        $this->assertSame('jt3HVqwVhg3DiBOY8CKIAA0A5iE=', $method->invoke($storage, $params));
    }
}
