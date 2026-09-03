<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CDN 服务商管理(admin):CRUD / 默认互斥 / 启停 / 连通测试 / 缓存刷新。
 * 凭据加密存储(Encryptable),接口只返回脱敏 mask:driver_****末4位。
 */

namespace plugin\ads_api\controller\v1\admin;

use Webman\Http\Request;
use Webman\Http\Response;
use app\support\ApiResponse;
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\Storage;
use Illuminate\Database\Capsule\Manager as DB;

class CdnProviderController
{
    use \erik\support\ControllerTrait;

    protected array $drivers = ['local', 'oss', 'cos', 's3'];
    protected array $cdnDrivers = ['none', 'aliyun', 'cloudflare', 'cloudfront'];

    /** 凭据绝不出接口,仅返回脱敏 driver_****末4位 */
    protected function present(CdnProvider $p): array
    {
        $mask = fn (?string $v) => $v ? $p->driver . '_****' . substr($v, -4) : null;
        return [
            'id'                => $p->id,
            'name'              => $p->name,
            'driver'            => $p->driver,
            'bucket'            => $p->bucket,
            'region'            => $p->region,
            'endpoint'          => $p->endpoint,
            'access_key_masked' => $mask($p->access_key),
            'secret_key_masked' => $mask($p->secret_key),
            'cdn_domain'        => $p->cdn_domain,
            'cdn_driver'        => $p->cdn_driver,
            'cdn_token_masked'  => $mask($p->cdn_token),
            'enabled'           => (bool) $p->enabled,
            'is_default'        => (bool) $p->is_default,
            'status'            => (string) $p->status,
            'created_at'        => (string) $p->created_at,
            'updated_at'        => (string) $p->updated_at,
        ];
    }

    /** 默认转移:把默认交给剩余第一个 enabled 的 provider(无则不动) */
    protected static function transferDefaultFrom(CdnProvider $p): void
    {
        $next = CdnProvider::query()->where('enabled', 1)->where('id', '!=', $p->id)->first();
        if ($next) {
            $next->is_default = true;
            $next->save();
            CdnProvider::query()->where('id', $p->id)->update(['is_default' => 0]);
        }
    }

    /**
     * @Title("CDN 服务商列表")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers")
     * @Method("GET")
     */
    public function index(Request $request): Response
    {
        $items = CdnProvider::query()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn (CdnProvider $p) => $this->present($p))
            ->values()
            ->toArray();
        return ApiResponse::success($items);
    }

    /**
     * @Title("创建 CDN 服务商")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers")
     * @Method("POST")
     */
    public function store(Request $request): Response
    {
        $data = $request->post();
        $name = trim((string) ($data['name'] ?? ''));
        $driver = strtolower((string) ($data['driver'] ?? 'oss'));
        if ($name === '') return ApiResponse::error('Name is required');
        if (!in_array($driver, $this->drivers, true)) return ApiResponse::error('Invalid driver');
        if (isset($data['cdn_driver']) && $data['cdn_driver'] !== '' && !in_array((string) $data['cdn_driver'], $this->cdnDrivers, true)) {
            return ApiResponse::error('Invalid cdn_driver');
        }

        $provider = null;
        DB::transaction(function () use ($data, $name, $driver, &$provider) {
            $provider = CdnProvider::create([
                'name'       => $name,
                'driver'     => $driver,
                'bucket'     => $data['bucket'] ?? null,
                'region'     => $data['region'] ?? null,
                'endpoint'   => $data['endpoint'] ?? null,
                'access_key' => $data['access_key'] ?? null,
                'secret_key' => $data['secret_key'] ?? null,
                'cdn_domain' => $data['cdn_domain'] ?? null,
                'cdn_driver' => ($data['cdn_driver'] ?? '') !== '' ? $data['cdn_driver'] : null,
                'cdn_token'  => $data['cdn_token'] ?? null,
                'enabled'    => (int) !empty($data['enabled']),
            ]);
            if (!empty($data['is_default']) || CdnProvider::count() === 1) {
                static::markDefault($provider->id);
                $provider->refresh();
            }
        });

        return ApiResponse::success($this->present($provider), 'Created');
    }

    /**
     * @Title("更新 CDN 服务商")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers/{id}")
     * @Method("PUT")
     */
    public function update(Request $request, int $id): Response
    {
        $provider = CdnProvider::findOrFail($id);
        $data = $request->post();
        $driver = strtolower((string) ($data['driver'] ?? $provider->driver));
        if (!in_array($driver, $this->drivers, true)) return ApiResponse::error('Invalid driver');
        if (isset($data['cdn_driver']) && $data['cdn_driver'] !== '' && !in_array((string) $data['cdn_driver'], $this->cdnDrivers, true)) {
            return ApiResponse::error('Invalid cdn_driver');
        }
        // 停用当前默认 → 先转交默认给剩余第一个 enabled
        if (isset($data['enabled']) && empty($data['enabled']) && $provider->is_default) {
            static::transferDefaultFrom($provider);
        }

        $fill = [
            'driver'     => $driver,
            'bucket'     => $data['bucket'] ?? $provider->bucket,
            'region'     => $data['region'] ?? $provider->region,
            'endpoint'   => $data['endpoint'] ?? $provider->endpoint,
            'cdn_domain' => $data['cdn_domain'] ?? $provider->cdn_domain,
            'cdn_driver' => isset($data['cdn_driver']) ? (($data['cdn_driver'] !== '') ? $data['cdn_driver'] : null) : $provider->cdn_driver,
        ];
        if (isset($data['name']) && trim((string) $data['name']) !== '') {
            $fill['name'] = trim((string) $data['name']);
        }
        // 空字符串 = 不修改,避免前端回填不了明文凭据
        // ponytail: 由此无法清空凭据;需要时前端加显式 clear 标记
        foreach (['access_key', 'secret_key', 'cdn_token'] as $k) {
            if (isset($data[$k]) && $data[$k] !== '') $fill[$k] = $data[$k];
        }
        if (isset($data['enabled'])) $fill['enabled'] = (int) !empty($data['enabled']);

        DB::transaction(function () use ($provider, $fill, $data) {
            $provider->update($fill);
            if (!empty($data['is_default']) && !$provider->is_default) {
                static::markDefault($provider->id);
            } elseif (isset($data['is_default']) && empty($data['is_default']) && $provider->is_default) {
                static::transferDefaultFrom($provider);
            }
        });

        return ApiResponse::success($this->present($provider->fresh()), 'Updated');
    }

    /**
     * @Title("删除 CDN 服务商")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers/{id}")
     * @Method("DELETE")
     */
    public function destroy(int $id): Response
    {
        $provider = CdnProvider::findOrFail($id);
        $wasDefault = (bool) $provider->is_default;
        DB::transaction(function () use ($provider, $wasDefault) {
            $provider->delete();
            if ($wasDefault) {
                $next = CdnProvider::query()->where('enabled', 1)->first();
                if ($next) {
                    $next->is_default = true;
                    $next->save();
                }
            }
        });
        return ApiResponse::success(null, 'Deleted');
    }

    /**
     * @Title("设默认 CDN 服务商")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers/{id}/default")
     * @Method("PUT")
     */
    public function setDefault(int $id): Response
    {
        $provider = CdnProvider::findOrFail($id);
        DB::transaction(function () use ($provider) {
            static::markDefault($provider->id);
        });
        return ApiResponse::success($this->present($provider->fresh()), 'Default set');
    }

    /**
     * @Title("启停 CDN 服务商")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers/{id}/toggle")
     * @Method("PUT")
     */
    public function toggle(int $id): Response
    {
        $provider = CdnProvider::findOrFail($id);
        $enabled = !$provider->enabled;
        if (!$enabled && $provider->is_default) {
            $next = CdnProvider::query()->where('enabled', 1)->where('id', '!=', $provider->id)->first();
            if (!$next) {
                return ApiResponse::error('Cannot disable the only enabled default provider');
            }
        }
        DB::transaction(function () use ($provider, $enabled) {
            if (!$enabled && $provider->is_default) {
                $next = CdnProvider::query()->where('enabled', 1)->where('id', '!=', $provider->id)->first();
                $next->is_default = true;
                $next->save();
                $provider->is_default = false;
            }
            $provider->enabled = $enabled;
            if ($enabled && !CdnProvider::query()->where('is_default', 1)->exists()) {
                static::markDefault($provider->id);
            }
            $provider->save();
        });
        return ApiResponse::success($this->present($provider->fresh()), 'Toggled');
    }

    /**
     * @Title("测试 CDN 服务商连通性")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers/{id}/test")
     * @Method("POST")
     */
    public function test(int $id): Response
    {
        $provider = CdnProvider::findOrFail($id);
        try {
            $ok = Storage::forProvider($provider)->test();
        } catch (\Throwable $e) {
            $provider->status = 'fail: ' . mb_substr($e->getMessage(), 0, 200);
            $provider->save();
            \support\Log::error("CDN provider {$id} test failed: " . $e->getMessage());
            return ApiResponse::error('Connectivity test failed');
        }
        $provider->status = $ok ? 'ok' : 'fail';
        $provider->save();
        return ApiResponse::success(['ok' => $ok, 'driver' => $provider->driver, 'status' => $provider->status]);
    }

    /**
     * @Title("CDN 缓存刷新")
     * @Group("CDN")
     * @Url("/api/v1/admin/cdn/providers/{id}/purge")
     * @Method("POST")
     */
    public function purge(Request $request, int $id): Response
    {
        $provider = CdnProvider::findOrFail($id);
        $paths = $request->post('paths', []);
        if (!is_array($paths) || !$paths) return ApiResponse::error('paths is required');
        if (!$provider->cdn_driver || $provider->cdn_driver === 'none') {
            return ApiResponse::error('cdn_driver not configured', 400);
        }
        if (!$provider->cdn_domain) {
            return ApiResponse::error('cdn_domain not configured', 400);
        }
        // 相对路径补全为 CDN 绝对 URL(CDN API 要求)
        $urls = array_map(fn ($p) => preg_match('#^https?://#', (string) $p)
            ? (string) $p
            : 'https://' . rtrim($provider->cdn_domain, '/') . '/' . ltrim((string) $p, '/'), $paths);
        $purged = Storage::forProvider($provider)->purge($urls);
        if ($purged === 0) {
            return ApiResponse::error('Purge not implemented for cdn_driver ' . $provider->cdn_driver, 400);
        }
        return ApiResponse::success(['purged' => $purged]);
    }

    // ponytail: 切换默认 provider 后旧 bucket 上的对象不迁移;需要时加后台迁移任务
    protected static function markDefault(int $id): void
    {
        CdnProvider::query()->where('is_default', 1)->update(['is_default' => 0]);
        CdnProvider::query()->where('id', $id)->update(['is_default' => 1]);
    }
}
