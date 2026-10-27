<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * QuotaService — 多租户 SaaS 配额 MVP（Phase 10 Task 4）。
 *
 * 表结构核实：ads_tenants 存在 plan 字段，类型 ENUM('free','pro','enterprise')，
 * 取值与任务约定的 lite/standard/full 不一致，故做一次映射；
 * 未知/缺失 plan 一律按 full（任务约定默认 full）。
 *
 * 用量口径：
 * - accounts  : ads_platform_accounts 中 tenant_id 计数（当前绑定账户数）
 * - campaigns : ads_campaigns 中 tenant_id 计数（计划数）
 * - sync_today: 今日同步次数。MVP 无独立同步审计表，选 platform_accounts.last_sync_at
 *   计数（last_sync_at 落在今天的账户数）作为代理口径——每次同步完成会更新该字段
 *   （DataSyncTask / AccountController::sync）。更精确的逐次计数需引入同步审计表，留待迭代。
 */

namespace plugin\ads_tenant\service;

use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class QuotaService
{
    /** ads_tenants.plan → 配额版本线 */
    public const PLAN_TIER_MAP = [
        'free'       => 'lite',
        'pro'        => 'standard',
        'enterprise' => 'full',
    ];

    /** 未知/缺失 plan 时的默认版本线 */
    public const DEFAULT_TIER = 'full';

    /** 版本线配额：账号上限 / 计划上限 / 每日同步次数上限 */
    public const TIER_LIMITS = [
        'lite'     => ['account_limit' => 5,    'campaign_limit' => 50,    'sync_daily' => 100],
        'standard' => ['account_limit' => 20,   'campaign_limit' => 500,   'sync_daily' => 1000],
        'full'     => ['account_limit' => 100,  'campaign_limit' => 5000,  'sync_daily' => 10000],
    ];

    /** 可拦截的资源名（usage() 键名 → TIER_LIMITS 键名） */
    public const RESOURCE_LIMIT_MAP = [
        'accounts'   => 'account_limit',
        'campaigns'  => 'campaign_limit',
        'sync_today' => 'sync_daily',
    ];

    /**
     * 解析租户 plan 对应的配额版本线（纯逻辑，可单测）。
     */
    public static function resolveTier(?string $plan): string
    {
        if ($plan === null || $plan === '') {
            return self::DEFAULT_TIER;
        }
        return self::PLAN_TIER_MAP[$plan] ?? self::DEFAULT_TIER;
    }

    /**
     * 获取某版本线的配额数组（纯逻辑，可单测；未知版本线回退 full）。
     */
    public static function limitsFor(string $tier): array
    {
        return self::TIER_LIMITS[$tier] ?? self::TIER_LIMITS[self::DEFAULT_TIER];
    }

    /**
     * 纯逻辑用量检查：used >= limit 视为超限（已满即拦截）。
     *
     * @return array{used:int, limit:int, exceeded:bool, remaining:int}
     */
    public static function checkQuota(int $used, int $limit): array
    {
        return [
            'used'      => $used,
            'limit'     => $limit,
            'exceeded'  => $used >= $limit,
            'remaining' => max($limit - $used, 0),
        ];
    }

    // ---------------- 以下依赖 DB ----------------

    /**
     * 当前租户的配额版本线（读 ads_tenants.plan）。
     */
    public function tierForTenant(int $tenantId): string
    {
        $plan = DB::table('ads_tenants')->where('id', $tenantId)->value('plan');
        return static::resolveTier($plan);
    }

    /**
     * 当前租户的用量统计（口径见类注释）。
     */
    public function usage(int $tenantId): array
    {
        $todayStart = date('Y-m-d 00:00:00');

        $accounts = (int) DB::table('ads_platform_accounts')
            ->where('tenant_id', $tenantId)
            ->count();

        $campaigns = (int) DB::table('ads_campaigns')
            ->where('tenant_id', $tenantId)
            ->count();

        // sync_today 代理口径：今日完成过同步的账户数（last_sync_at 落在今天）
        $syncToday = (int) DB::table('ads_platform_accounts')
            ->where('tenant_id', $tenantId)
            ->where('last_sync_at', '>=', $todayStart)
            ->count();

        return [
            'accounts'   => $accounts,
            'campaigns'  => $campaigns,
            'sync_today' => $syncToday,
        ];
    }

    /**
     * 拦截检查：资源未超限返回 null；超限返回结构化详情（含面向用户的提示语）。
     *
     * @param string $resource accounts|campaigns|sync_today
     * @return array{resource:string, used:int, limit:int, message:string}|null
     */
    public function exceeded(string $resource, int $tenantId): ?array
    {
        if (!isset(self::RESOURCE_LIMIT_MAP[$resource])) {
            throw new InvalidArgumentException("Unknown quota resource: {$resource}");
        }

        $usage = $this->usage($tenantId);
        $limits = static::limitsFor($this->tierForTenant($tenantId));

        $used = $usage[$resource];
        $limit = $limits[self::RESOURCE_LIMIT_MAP[$resource]];

        if ($used >= $limit) {
            return [
                'resource' => $resource,
                'used'     => $used,
                'limit'    => $limit,
                'message'  => "配额超限: {$resource} 已达 {$used}/{$limit}",
            ];
        }
        return null;
    }
}
