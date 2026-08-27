<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 同步：状态摘要 / 错误列表；转化：回传 / 列表；租户：配额。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\SyncController;
use plugin\ads_api\controller\v1\ConversionController;
use plugin\ads_api\controller\v1\TenantController;

class SyncConversionTenantApiTest extends ApiTestCase
{
    public function testSyncStatusEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/sync/status');
        $body = $this->assertSuccess((new SyncController())->status($request));
        $this->assertEquals([], $body['data']['accounts']);
        $this->assertEquals(0, $body['data']['summary']['total_accounts']);
    }

    public function testSyncStatusWithAccount(): void
    {
        $accountId = $this->seedAccount(['last_sync_at' => date('Y-m-d H:i:s')]);
        $request = $this->authedRequest('GET', '/api/sync/status');
        $body = $this->assertSuccess((new SyncController())->status($request));

        $this->assertEquals(1, $body['data']['summary']['total_accounts']);
        $this->assertEquals(1, $body['data']['summary']['synced_24h']);
        $this->assertEquals($accountId, $body['data']['accounts'][0]['id']);
    }

    public function testSyncErrorsEmptyAndSeeded(): void
    {
        $controller = new SyncController();

        $empty = $this->authedRequest('GET', '/api/sync/errors');
        $body = $this->assertSuccess($controller->errors($empty));
        $this->assertEquals(0, $body['data']['pagination']['total']);

        $accountId = $this->seedAccount();
        DB::table('ads_sync_errors')->insert([
            'id'                  => $this->nextId(),
            'platform_account_id' => $accountId,
            'platform'            => 'mock',
            'error_message'       => 'token expired',
            'retry_count'         => 1,
            'last_error'          => '401',
            'next_retry_at'       => date('Y-m-d H:i:s'),
            'created_at'          => now(),
        ]);

        $body = $this->assertSuccess($controller->errors($empty));
        $this->assertEquals(1, $body['data']['pagination']['total']);
        $this->assertEquals('token expired', $body['data']['list'][0]['error_message']);
        $this->assertEquals('Mock Account', $body['data']['list'][0]['account_name']);
    }

    public function testConversionStoreSuccess(): void
    {
        $campaignId = $this->seedCampaign();
        $request = $this->authedRequest('POST', '/api/conversions', [
            'platform'        => 'mock',
            'campaign_id'     => $campaignId,
            'order_id'        => 'ord-test-1',
            'conversion_time' => date('Y-m-d H:i:s'),
            'value'           => 500,
        ]);

        $body = $this->assertSuccess((new ConversionController())->store($request));
        $this->assertEquals('ord-test-1', $body['data']['order_id']);
    }

    public function testConversionStoreDuplicate(): void
    {
        $campaignId = $this->seedCampaign();
        $payload = [
            'platform'        => 'mock',
            'campaign_id'     => $campaignId,
            'order_id'        => 'ord-dup',
            'conversion_time' => date('Y-m-d H:i:s'),
            'value'           => 100,
        ];
        $controller = new ConversionController();
        $this->assertSuccess($controller->store($this->authedRequest('POST', '/api/conversions', $payload)));

        $dup = $this->authedRequest('POST', '/api/conversions', $payload);
        $this->assertError($controller->store($dup), 400);
    }

    public function testConversionStoreValidationErrors(): void
    {
        $controller = new ConversionController();

        // 缺必填字段
        $bad = $this->authedRequest('POST', '/api/conversions', ['platform' => 'mock']);
        $this->assertError($controller->store($bad), 400);

        // 负数金额
        $neg = $this->authedRequest('POST', '/api/conversions', [
            'platform' => 'mock', 'campaign_id' => $this->seedCampaign(),
            'order_id' => 'ord-neg', 'conversion_time' => date('Y-m-d H:i:s'), 'value' => -1,
        ]);
        $this->assertError($controller->store($neg), 400);

        // 非法时间
        $badTime = $this->authedRequest('POST', '/api/conversions', [
            'platform' => 'mock', 'campaign_id' => $this->seedCampaign(),
            'order_id' => 'ord-time', 'conversion_time' => 'not-a-date', 'value' => 1,
        ]);
        $this->assertError($controller->store($badTime), 400);

        // 不存在的 campaign
        $noCampaign = $this->authedRequest('POST', '/api/conversions', [
            'platform' => 'mock', 'campaign_id' => 999999,
            'order_id' => 'ord-nc', 'conversion_time' => date('Y-m-d H:i:s'), 'value' => 1,
        ]);
        $this->assertError($controller->store($noCampaign), 400);

        $this->assertEquals(0, DB::table('ads_conversions')->count());
    }

    public function testConversionIndex(): void
    {
        $campaignId = $this->seedCampaign();
        $request = $this->authedRequest('POST', '/api/conversions', [
            'platform' => 'mock', 'campaign_id' => $campaignId,
            'order_id' => 'ord-list', 'conversion_time' => date('Y-m-d H:i:s'), 'value' => 888,
        ]);
        $this->assertSuccess((new ConversionController())->store($request));

        $list = $this->authedRequest('GET', '/api/conversions', [], [], ['platform' => 'mock']);
        $body = $this->assertSuccess((new ConversionController())->index($list));
        $this->assertEquals(1, $body['data']['pagination']['total']);
        $this->assertEquals('ord-list', $body['data']['list'][0]['order_id']);
    }

    public function testTenantQuota(): void
    {
        $this->seedAccount();
        $this->seedCampaign();

        $request = $this->authedRequest('GET', '/api/tenant/quota');
        $body = $this->assertSuccess((new TenantController())->quota($request));

        // PLAN_TIER_MAP: enterprise → full 版本线
        $this->assertEquals('full', $body['data']['plan']);
        $this->assertArrayHasKey('limits', $body['data']);
        $this->assertArrayHasKey('usage', $body['data']);
        // seedCampaign 内部也会 seedAccount → 共 2 个账户
        $this->assertEquals(2, $body['data']['usage']['accounts']);
    }
}
