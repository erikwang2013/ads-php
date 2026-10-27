<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 素材：列表 / 详情 / 删除 / 上传（无文件与非法类型错误路径）。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\AssetController;

class AssetApiTest extends ApiTestCase
{
    protected function seedAsset(array $overrides = []): int
    {
        $id = $this->nextId();
        DB::table('ads_assets')->insert(array_merge([
            'id'         => $id,
            'tenant_id'  => $this->tenantId,
            'type'       => 'image',
            'filename'   => 'banner.png',
            'mime_type'  => 'image/png',
            'size'       => 1024,
            'url'        => '/uploads/assets/banner.png',
            'width'      => 1200,
            'height'     => 600,
            'created_at' => now(),
        ], $overrides));
        return $id;
    }

    public function testAssetListEmptyAndSeeded(): void
    {
        $controller = new AssetController();

        $empty = $this->authedRequest('GET', '/api/assets');
        $body = $this->assertSuccess($controller->index($empty));
        $this->assertEquals(0, $body['data']['pagination']['total']);

        $this->seedAsset();
        $body = $this->assertSuccess($controller->index($empty));
        $this->assertEquals(1, $body['data']['pagination']['total']);

        $filtered = $this->authedRequest('GET', '/api/assets', [], [], ['type' => 'video']);
        $body = $this->assertSuccess($controller->index($filtered));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testAssetShow(): void
    {
        $id = $this->seedAsset();
        $body = $this->assertSuccess((new AssetController())->show($this->authedRequest('GET', "/api/assets/{$id}"), $id));
        $this->assertEquals('banner.png', $body['data']['filename']);
    }

    public function testAssetShowNotFound(): void
    {
        $this->assertError((new AssetController())->show($this->authedRequest('GET', '/api/assets/999999'), 999999), 1);
    }

    public function testAssetDestroy(): void
    {
        $id = $this->seedAsset();
        $this->assertSuccess((new AssetController())->destroy($this->authedRequest('DELETE', "/api/assets/{$id}"), $id));
        $this->assertEquals(0, DB::table('ads_assets')->count());
    }

    public function testAssetDestroyNotFound(): void
    {
        $this->assertError((new AssetController())->destroy($this->authedRequest('DELETE', '/api/assets/999999'), 999999), 1);
    }

    public function testAssetUploadNoFile(): void
    {
        $request = $this->authedRequest('POST', '/api/assets/upload');
        $this->assertError((new AssetController())->upload($request), 1);
    }
}
