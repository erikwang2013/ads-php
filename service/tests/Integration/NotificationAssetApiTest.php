<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 通知：列表 / 未读数 / 标记已读 / 全部已读。
 * 素材：列表 / 详情 / 删除 / 上传（无文件与非法类型错误路径）。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\NotificationController;
use plugin\ads_api\controller\v1\AssetController;

class NotificationAssetApiTest extends ApiTestCase
{
    protected function seedNotification(array $overrides = []): int
    {
        $id = $this->nextId();
        DB::table('ads_notifications')->insert(array_merge([
            'id'         => $id,
            'tenant_id'  => $this->tenantId,
            'type'       => 'alert',
            'title'      => '测试通知',
            'content'    => '花费超限提醒',
            'is_read'    => 0,
            'created_at' => now(),
        ], $overrides));
        return $id;
    }

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

    public function testNotificationListEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/notifications');
        $body = $this->assertSuccess((new NotificationController())->index($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testNotificationListWithFilters(): void
    {
        $this->seedNotification();
        $this->seedNotification(['is_read' => 1, 'type' => 'system']);

        $all = $this->authedRequest('GET', '/api/notifications');
        $body = $this->assertSuccess((new NotificationController())->index($all));
        $this->assertEquals(2, $body['data']['pagination']['total']);

        $unread = $this->authedRequest('GET', '/api/notifications', [], [], ['is_read' => 0]);
        $body = $this->assertSuccess((new NotificationController())->index($unread));
        $this->assertEquals(1, $body['data']['pagination']['total']);
    }

    public function testUnreadCount(): void
    {
        $this->seedNotification();
        $this->seedNotification(['is_read' => 1]);

        $request = $this->authedRequest('GET', '/api/notifications/unread-count');
        $body = $this->assertSuccess((new NotificationController())->unreadCount($request));
        $this->assertEquals(1, $body['data']['count']);
    }

    public function testMarkRead(): void
    {
        $id = $this->seedNotification();
        $request = $this->authedRequest('POST', "/api/notifications/$id/read");

        $this->assertSuccess((new NotificationController())->markRead($request, $id));
        $this->assertEquals(1, (int) DB::table('ads_notifications')->find($id)->is_read);
    }

    public function testMarkReadNotFound(): void
    {
        $request = $this->authedRequest('POST', '/api/notifications/999999/read');
        $this->assertError((new NotificationController())->markRead($request, 999999), 1);
    }

    public function testMarkAllRead(): void
    {
        $this->seedNotification();
        $this->seedNotification();

        $request = $this->authedRequest('POST', '/api/notifications/read-all');
        $this->assertSuccess((new NotificationController())->markAllRead($request));
        $this->assertEquals(0, (int) DB::table('ads_notifications')->where('is_read', 0)->count());
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
        $body = $this->assertSuccess((new AssetController())->show($id));
        $this->assertEquals('banner.png', $body['data']['filename']);
    }

    public function testAssetShowNotFound(): void
    {
        $this->assertError((new AssetController())->show(999999), 1);
    }

    public function testAssetDestroy(): void
    {
        $id = $this->seedAsset();
        $this->assertSuccess((new AssetController())->destroy($id));
        $this->assertEquals(0, DB::table('ads_assets')->count());
    }

    public function testAssetDestroyNotFound(): void
    {
        $this->assertError((new AssetController())->destroy(999999), 1);
    }

    public function testAssetUploadNoFile(): void
    {
        $request = $this->authedRequest('POST', '/api/assets/upload');
        $this->assertError((new AssetController())->upload($request), 1);
    }
}
