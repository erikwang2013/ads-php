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
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\Storage;
use plugin\ads_storage\src\driver\LocalStorage;

class AssetStorageTest extends ApiTestCase
{
    /** 建一个 driver=local + CDN 域名的默认 provider,验证读取时拼接 */
    protected function seedCdnProvider(string $cdnDomain = 'cdn.example.com'): int
    {
        $provider = CdnProvider::create([
            'name'       => 'Local CDN',
            'driver'     => 'local',
            'cdn_domain' => $cdnDomain,
            'enabled'    => 1,
            'is_default' => 1,
            'status'     => 1,
        ]);
        return (int) $provider->id;
    }

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
        $request->tenantId = $this->tenantId;
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

    public function testShowAndDestroyAreTenantScoped(): void
    {
        $controller = new AssetController();
        $body = $this->assertSuccess(
            $controller->upload($this->multipartUploadRequest('/api/assets/upload', 'test.png', 'image/png', 'x'))
        );
        $id = (int) $body['data']['id'];

        // tenant 2 不能查看 tenant 1 的素材
        $other = $this->authedRequest('GET', "/api/assets/{$id}");
        $other->tenantId = 2;
        $this->assertError($controller->show($other, $id), 1);

        // tenant 2 不能删除:对象与 DB 行都保留
        $del = $this->authedRequest('DELETE', "/api/assets/{$id}");
        $del->tenantId = 2;
        $this->assertError($controller->destroy($del, $id), 1);
        $this->assertEquals(1, DB::table('ads_assets')->count());
        $this->assertFileExists(public_path() . $body['data']['url']);
    }

    public function testStorageDispatchReadsDefaultProvider(): void
    {
        $this->seedCdnProvider();
        $this->assertInstanceOf(LocalStorage::class, Storage::driver());
        // 默认 provider 存在但 driver=local 时,URL 不拼 CDN(本地直出路径)
        $this->assertEquals('/uploads/assets/x.png', Storage::publicUrl('x.png'));
    }

    public function testCdnDomainJoinedOnReadOnly(): void
    {
        $this->seedCdnProvider('cdn.example.com');
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
        $controller = new AssetController();
        $body = $this->assertSuccess(
            $controller->upload($this->multipartUploadRequest('/api/assets/upload', 'test.png', 'image/png', $png))
        );
        $url = $body['data']['url'];
        $this->assertMatchesRegularExpression('#^https://cdn\.example\.com/uploads/assets/\d{8}/[0-9a-f]{32}\.png$#', $url);

        // DB 仍是相对路径
        $row = DB::table('ads_assets')->find($body['data']['id']);
        $this->assertMatchesRegularExpression('#^/uploads/assets/\d{8}/[0-9a-f]{32}\.png$#', $row->url);

        // 列表读取时拼接
        $list = $this->assertSuccess($controller->index($this->authedRequest('GET', '/api/assets')));
        $this->assertEquals($url, array_values($list['data']['list'])[0]['url']);

        // 删除:文件删除且 DB 行消失
        $this->assertSuccess($controller->destroy($this->authedRequest('DELETE', '/api/assets/' . $body['data']['id']), (int) $body['data']['id']));
        $this->assertEquals(0, DB::table('ads_assets')->count());
        $this->assertFileDoesNotExist(public_path() . substr($row->url, 1));
    }

    public function testPresignRejectedForLocalDriver(): void
    {
        $controller = new AssetController();
        $request = $this->authedRequest('POST', '/api/assets/presign', [
            'filename'  => 'video.mp4',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertError($controller->presign($request), 1);
    }

    public function testRegisterCreatesAssetFromDirectUploadKey(): void
    {
        $this->seedCdnProvider();
        $key = date('Ymd') . '/' . str_repeat('a', 32) . '.mp4';
        $request = $this->authedRequest('POST', '/api/assets/register', [
            'key'       => $key,
            'filename'  => 'video.mp4',
            'mime_type' => 'video/mp4',
            'size'      => 1024,
        ]);
        $body = $this->assertSuccess((new AssetController())->register($request));
        $this->assertEquals('video', $body['data']['type']);
        $this->assertEquals('https://cdn.example.com/uploads/assets/' . $key, $body['data']['url']);

        $row = DB::table('ads_assets')->find($body['data']['id']);
        $this->assertEquals('/uploads/assets/' . $key, $row->url);

        // 非法 key(路径穿越)被拒
        $bad = $this->authedRequest('POST', '/api/assets/register', [
            'key'       => '../../etc/passwd',
            'filename'  => 'x.png',
            'mime_type' => 'image/png',
            'size'      => 1,
        ]);
        $this->assertError((new AssetController())->register($bad), 1);
    }
}
