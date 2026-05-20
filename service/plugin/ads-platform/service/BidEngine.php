<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * BidEngine evaluates auto-bidding rules and executes campaign adjustments.
 * Reuses the metric query pattern from AlertEngine.
 */

namespace plugin\ads_platform\service;

use plugin\ads_platform\model\BidRule;
use plugin\ads_platform\model\BidLog;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_account\model\PlatformAccount;
use Illuminate\Database\Capsule\Manager as DB;

class BidEngine
{
    protected const METRIC_SQL = [
        'cost'        => 'COALESCE(SUM(cost), 0)',
        'impressions' => 'COALESCE(SUM(impressions), 0)',
        'clicks'      => 'COALESCE(SUM(clicks), 0)',
        'conversions' => 'COALESCE(SUM(conversions), 0)',
        'ctr'         => 'CASE WHEN COALESCE(SUM(impressions),0)>0 THEN COALESCE(SUM(clicks),0)/COALESCE(SUM(impressions),0) ELSE 0 END',
        'cvr'         => 'CASE WHEN COALESCE(SUM(clicks),0)>0 THEN COALESCE(SUM(conversions),0)/COALESCE(SUM(clicks),0) ELSE 0 END',
        'roi'         => 'CASE WHEN COALESCE(SUM(cost),0)>0 THEN COALESCE(SUM(conversions),0)/COALESCE(SUM(cost),0)*100 ELSE 0 END',
    ];

    public function evaluate(BidRule $rule): ?BidLog
    {
        $metricSql = self::METRIC_SQL[$rule->metric] ?? null;
        if (!$metricSql) return null;

        $query = DB::table('erik_report_metrics')
            ->where('tenant_id', $rule->tenant_id)
            ->where('date', date('Y-m-d'));

        if ($rule->scope === 'platform' && $rule->platform) {
            $query->where('platform', $rule->platform);
        }
        if ($rule->scope === 'campaign' && $rule->campaign_id) {
            $query->where('campaign_id', $rule->campaign_id);
        }

        $result = $query->selectRaw("{$metricSql} as metric_value")->first();
        $metricValue = (float) ($result->metric_value ?? 0);

        if (!$this->compare($metricValue, $rule->threshold, $rule->condition)) {
            return null;
        }

        if ($this->inCooldown($rule)) {
            return null;
        }

        return $this->execute($rule, $metricValue);
    }

    protected function compare(float $value, float $threshold, string $condition): bool
    {
        return match ($condition) {
            'gt'  => $value > $threshold,
            'gte' => $value >= $threshold,
            'lt'  => $value < $threshold,
            'lte' => $value <= $threshold,
            default => false,
        };
    }

    protected function inCooldown(BidRule $rule): bool
    {
        return BidLog::where('rule_id', $rule->id)
            ->where('created_at', '>=', now()->subMinutes($rule->cooldown_minutes))
            ->exists();
    }

    protected function execute(BidRule $rule, float $metricValue): ?BidLog
    {
        if ($rule->scope === 'campaign' && $rule->campaign_id) {
            return $this->executeForCampaign($rule, (int) $rule->campaign_id, $metricValue);
        }

        $campaigns = DB::table('erik_campaigns')
            ->where('tenant_id', $rule->tenant_id)
            ->where('status', 'enabled')
            ->when($rule->scope === 'platform' && $rule->platform, fn($q) => $q->where('platform', $rule->platform))
            ->get();

        foreach ($campaigns as $campaign) {
            $log = $this->executeForCampaign($rule, $campaign->id, $metricValue);
            if ($log) return $log;
        }

        return null;
    }

    protected function executeForCampaign(BidRule $rule, int $campaignId, float $metricValue): ?BidLog
    {
        $campaign = DB::table('erik_campaigns')->find($campaignId);
        if (!$campaign || $campaign->status !== 'enabled') return null;

        $account = PlatformAccount::find($campaign->platform_account_id);
        if (!$account) return null;

        $adapter = AdapterRegistry::get($campaign->platform);
        if (!$adapter) return null;

        $logData = [
            'rule_id'      => $rule->id,
            'tenant_id'    => $rule->tenant_id,
            'campaign_id'  => $campaignId,
            'metric_value' => $metricValue,
            'action_type'  => $rule->action_type,
        ];

        try {
            switch ($rule->action_type) {
                case 'adjust_budget':
                    return $this->adjustBudget($rule, $campaign, $account, $adapter, $logData);

                case 'toggle_pause':
                    return $this->toggleStatus($campaign, $account, $adapter, false, $logData);

                case 'toggle_enable':
                    return $this->toggleStatus($campaign, $account, $adapter, true, $logData);
            }
        } catch (\Throwable $e) {
            \support\Log::channel('default')->error("BidEngine error: {$e->getMessage()}", [
                'rule_id' => $rule->id, 'campaign_id' => $campaignId,
            ]);
        }

        return null;
    }

    protected function adjustBudget(BidRule $rule, $campaign, $account, $adapter, array $logData): BidLog
    {
        $currentBudget = (int) $campaign->daily_budget;
        $newBudget = $currentBudget + $rule->adjust_step;

        if ($rule->budget_min > 0) $newBudget = max($newBudget, $rule->budget_min);
        if ($rule->budget_max > 0) $newBudget = min($newBudget, $rule->budget_max);
        if ($newBudget <= 0 || $newBudget === $currentBudget) return null;

        $data = new CampaignData(name: $campaign->name, dailyBudget: $newBudget);
        $adapter->updateCampaign($account->access_token, $account->account_id_on_platform, $campaign->platform_campaign_id, $data);
        DB::table('erik_campaigns')->where('id', $campaign->id)->update(['daily_budget' => $newBudget, 'updated_at' => now()]);

        return BidLog::create(array_merge($logData, [
            'old_budget' => $currentBudget,
            'new_budget' => $newBudget,
        ]));
    }

    protected function toggleStatus($campaign, $account, $adapter, bool $enabled, array $logData): BidLog
    {
        $newStatus = $enabled ? 'enabled' : 'paused';
        if ($campaign->status === $newStatus) return null;

        $adapter->toggleCampaign($account->access_token, $account->account_id_on_platform, $campaign->platform_campaign_id, $enabled);
        DB::table('erik_campaigns')->where('id', $campaign->id)->update(['status' => $newStatus, 'updated_at' => now()]);

        return BidLog::create(array_merge($logData, [
            'old_status' => $campaign->status,
            'new_status' => $newStatus,
        ]));
    }
}
