<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 素材存储抽象层(ads-storage)最小集成测试:
 * 上传真实文件 → 响应 URL 格式与磁盘落盘 → 列表可见 → 删除 → DB 行与磁盘文件均消失。
 * workerman 从原始 multipart 请求体解析上传文件(不走 $_FILES),请求须携带真实 multipart body。
 */

namespace Tests\Integration;

use Webman\Http\Request;
use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\AssetController;
use plugin\ads_storage\src\Storage;
use plugin\ads_storage\src\driver\LocalStorage;

class AssetStorageTest extends ApiTestCase
{
    protected function multipartUploadRequest(string $path, string $filename, string $mime, string $content): Request
    {
        $boundary = '----asset-test-' . bin2hex(random_bytes(8));
        $body = "--$boundary\r\n"
            . "Content-Disposition: form-data; name=\"file\"; filename=\"$filename\"\r\n"
            . "Content-Type: $mime\r\n\r\n"
            . $content . "\r\n"
            . "--$boundary--\r\n";
        $raw = "POST $path HTTP/1.1\r\nHost: localhost\r\n"
            . "Content-Type: multipart/form-data; boundary=$boundary\r\n"
            . "Content-Length: " . strlen($body) . "\r\n\r\n" . $body;
        $request = new Request($raw);
        $request->userId = $this->userId;
        return $request;
    }

    public function testUploadListDestroyRoundTrip(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );

        $controller = new AssetController();
        $body = $this->assertSuccess(
            $controller->upload($this->multipartUploadRequest('/api/assets/upload', 'test.png', 'image/png', $png))
        );

        $url = $body['data']['url'];
        $this->assertMatchesRegularExpression('#^/uploads/assets/\d{8}/[0-9a-f]{32}\.png$#', $url);
        $this->assertFileExists(public_path() . $url);

        $list = $this->assertSuccess($controller->index($this->authedRequest('GET', '/api/assets')));
        $this->assertEquals(1, $list['data']['pagination']['total']);
        $this->assertEquals($url, array_values($list['data']['list'])[0]['url']);

        $this->assertSuccess($controller->destroy($this->authedRequest('DELETE', '/api/assets/' . $body['data']['id']), (int) $body['data']['id']));
        $this->assertEquals(0, DB::table('ads_assets')->count());
        $this->assertFileDoesNotExist(public_path() . $url);
    }

    public function testUploadUsesMimeExtensionNotClientFilename(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );

        $controller = new AssetController();
        $body = $this->assertSuccess(
            $controller->upload($this->multipartUploadRequest('/api/assets/upload', 'x.html', 'image/png', $png))
        );

        $url = $body['data']['url'];
        $this->assertMatchesRegularExpression('#^/uploads/assets/\d{8}/[0-9a-f]{32}\.png$#', $url);
        $this->assertFileExists(public_path() . $url);

        // 原始文件名仅作元数据保留
        $asset = DB::table('ads_assets')->find($body['data']['id']);
        $this->assertSame('x.html', $asset->filename);

        $this->assertSuccess($controller->destroy($this->authedRequest('DELETE', '/api/assets/' . $body['data']['id']), (int) $body['data']['id']));
    }

    public function testStorageDispatchDefaultsToLocal(): void
    {
        $this->assertInstanceOf(LocalStorage::class, Storage::driver());
        $this->assertEquals('/uploads/assets/x.png', Storage::publicUrl('x.png'));
    }

}
