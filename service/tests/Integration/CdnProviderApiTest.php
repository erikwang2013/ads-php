<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CDN 服务商管理 API:CRUD / 默认互斥 / 启停 / 凭据加密与脱敏 / test / purge。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\admin\CdnProviderController;
use plugin\ads_storage\model\CdnProvider;

class CdnProviderApiTest extends ApiTestCase
{
    protected function store(array $overrides = []): array
    {
        $body = array_merge([
            'name'        => '阿里云 OSS',
            'driver'      => 'oss',
            'bucket'      => 'ads-assets',
            'region'      => 'oss-cn-hangzhou',
            'access_key'  => 'AK-test-access-key-123456',
            'secret_key'  => 'SK-test-secret-key-654321',
            'cdn_domain'  => 'cdn.example.com',
            'cdn_driver'  => 'aliyun',
            'cdn_token'   => 'cdn-token-abc123',
            'enabled'     => 1,
        ], $overrides);
        $request = $this->authedRequest('POST', '/api/admin/cdn/providers', $body);
        return $this->assertSuccess((new CdnProviderController())->store($request));
    }

    public function testStoreMasksCredentialsAndEncryptsInDb(): void
    {
        $body = $this->store();
        $item = $body['data'];

        $this->assertEquals('oss_****3456', $item['access_key_masked']);
        $this->assertEquals('oss_****4321', $item['secret_key_masked']);
        $this->assertEquals('oss_****c123', $item['cdn_token_masked']);
        $this->assertArrayNotHasKey('access_key', $item);
        $this->assertArrayNotHasKey('secret_key', $item);

        // 库内为密文,不等于明文
        $row = DB::table('ads_cdn_providers')->find($item['id']);
        $this->assertNotEquals('AK-test-access-key-123456', $row->access_key);
        // 模型读取自动解密
        $this->assertEquals('AK-test-access-key-123456', CdnProvider::find($item['id'])->access_key);
    }

    public function testFirstProviderAutoBecomesDefault(): void
    {
        $first = $this->store(['name' => 'First']);
        $second = $this->store(['name' => 'Second']);

        $this->assertTrue($first['data']['is_default']);
        $this->assertFalse($second['data']['is_default']);
    }

    public function testSetDefaultIsExclusive(): void
    {
        $this->store(['name' => 'A']);
        $b = $this->store(['name' => 'B']);

        $this->assertSuccess((new CdnProviderController())->setDefault((int) $b['data']['id']));

        $this->assertEquals(1, CdnProvider::query()->where('is_default', 1)->count());
        $this->assertEquals($b['data']['id'], CdnProvider::query()->where('is_default', 1)->value('id'));
    }

    public function testUpdateWithEmptySecretKeepsOriginal(): void
    {
        $created = $this->store();
        $request = $this->authedRequest('PUT', "/api/admin/cdn/providers/{$created['data']['id']}", [
            'secret_key' => '',
            'region'     => 'oss-cn-beijing',
        ]);
        $this->assertSuccess((new CdnProviderController())->update($request, (int) $created['data']['id']));

        $provider = CdnProvider::find($created['data']['id']);
        $this->assertEquals('SK-test-secret-key-654321', $provider->secret_key);
        $this->assertEquals('oss-cn-beijing', $provider->region);
    }

    public function testUpdateSecretReplaces(): void
    {
        $created = $this->store();
        $request = $this->authedRequest('PUT', "/api/admin/cdn/providers/{$created['data']['id']}", [
            'secret_key' => 'SK-new-secret-9999',
        ]);
        $this->assertSuccess((new CdnProviderController())->update($request, (int) $created['data']['id']));
        $this->assertEquals('SK-new-secret-9999', CdnProvider::find($created['data']['id'])->secret_key);
    }

    public function testUpdateDisablingDefaultTransfersToNext(): void
    {
        $a = $this->store(['name' => 'A']);
        $b = $this->store(['name' => 'B']);
        $this->assertSuccess((new CdnProviderController())->setDefault((int) $b['data']['id']));

        $request = $this->authedRequest('PUT', "/api/admin/cdn/providers/{$b['data']['id']}", ['enabled' => 0]);
        $this->assertSuccess((new CdnProviderController())->update($request, (int) $b['data']['id']));

        $this->assertEquals($a['data']['id'], CdnProvider::query()->where('is_default', 1)->value('id'));
        $this->assertEquals(0, CdnProvider::find($b['data']['id'])->enabled);
    }

    public function testDestroyDefaultTransfersToNext(): void
    {
        $a = $this->store(['name' => 'A']);
        $b = $this->store(['name' => 'B']);

        $this->assertSuccess((new CdnProviderController())->destroy((int) $a['data']['id']));
        $this->assertEquals($b['data']['id'], CdnProvider::query()->where('is_default', 1)->value('id'));
        $this->assertEquals(1, CdnProvider::count());
    }

    public function testToggleDisablesNonDefault(): void
    {
        $a = $this->store(['name' => 'A']);
        $b = $this->store(['name' => 'B']);

        $this->assertSuccess((new CdnProviderController())->toggle((int) $b['data']['id']));
        $this->assertEquals(0, CdnProvider::find($b['data']['id'])->enabled);
        $this->assertEquals($a['data']['id'], CdnProvider::query()->where('is_default', 1)->value('id'));
    }

    public function testToggleDefaultTransfersToNext(): void
    {
        $a = $this->store(['name' => 'A']);
        $b = $this->store(['name' => 'B']);
        $this->assertSuccess((new CdnProviderController())->setDefault((int) $b['data']['id']));

        $this->assertSuccess((new CdnProviderController())->toggle((int) $b['data']['id']));
        $this->assertEquals(0, CdnProvider::find($b['data']['id'])->enabled);
        $this->assertEquals(0, CdnProvider::find($b['data']['id'])->is_default);
        $this->assertEquals($a['data']['id'], CdnProvider::query()->where('is_default', 1)->value('id'));
    }

    public function testToggleOnlyEnabledDefaultRejected(): void
    {
        $only = $this->store(['name' => 'Only']);

        $this->assertError((new CdnProviderController())->toggle((int) $only['data']['id']), 1);
        $this->assertEquals(1, CdnProvider::find($only['data']['id'])->enabled);
        $this->assertEquals(1, CdnProvider::find($only['data']['id'])->is_default);
    }

    public function testTestLocalOkAndMissingCredentialsFails(): void
    {
        $local = $this->store(['name' => 'Local', 'driver' => 'local', 'bucket' => null]);
        $body = $this->assertSuccess((new CdnProviderController())->test((int) $local['data']['id']));
        $this->assertTrue($body['data']['ok']);
        $this->assertEquals('ok', CdnProvider::find($local['data']['id'])->status);

        $oss = $this->store(['name' => 'No Creds', 'driver' => 'oss', 'access_key' => null, 'secret_key' => null]);
        $this->assertError((new CdnProviderController())->test((int) $oss['data']['id']), 1);
        $this->assertStringStartsWith('fail:', CdnProvider::find($oss['data']['id'])->status);
    }

    public function testPurgeRequiresPathsAndLocalOk(): void
    {
        $local = $this->store(['name' => 'Local', 'driver' => 'local', 'bucket' => null]);

        $noPaths = $this->authedRequest('POST', "/api/admin/cdn/providers/{$local['data']['id']}/purge", []);
        $this->assertError((new CdnProviderController())->purge($noPaths, (int) $local['data']['id']), 1);

        $ok = $this->authedRequest('POST', "/api/admin/cdn/providers/{$local['data']['id']}/purge", ['paths' => ['/uploads/assets/a.png']]);
        $body = $this->assertSuccess((new CdnProviderController())->purge($ok, (int) $local['data']['id']));
        $this->assertEquals(1, $body['data']['purged']);
    }

    public function testPurgeRejectedWhenCdnDriverMissing(): void
    {
        $provider = $this->store(['name' => 'No CDN', 'cdn_driver' => null]);
        $request = $this->authedRequest('POST', "/api/admin/cdn/providers/{$provider['data']['id']}/purge", ['paths' => ['/uploads/assets/a.png']]);
        $this->assertError((new CdnProviderController())->purge($request, (int) $provider['data']['id']), 400);
    }

    public function testListShowsMaskedOnly(): void
    {
        $this->store();
        $request = $this->authedRequest('GET', '/api/admin/cdn/providers');
        $body = $this->assertSuccess((new CdnProviderController())->index($request));
        $this->assertCount(1, $body['data']);
        $this->assertArrayNotHasKey('access_key', $body['data'][0]);
        $this->assertArrayNotHasKey('secret_key', $body['data'][0]);
        $this->assertArrayNotHasKey('cdn_token', $body['data'][0]);
    }
}
