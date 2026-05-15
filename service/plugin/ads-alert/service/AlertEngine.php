<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_alert\service;

use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\model\AlertLog;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder as QueryBuilder;

class AlertEngine
{
    protected const METRIC_SQL = [
        'cost'        => 'COALESCE(SUM(cost), 0)',
        'impressions' => 'COALESCE(SUM(impressions), 0)',
        'clicks'      => 'COALESCE(SUM(clicks), 0)',
        'conversions' => 'COALESCE(SUM(conversions), 0)',
        'ctr'         => 'CASE WHEN COALESCE(SUM(impressions), 0) > 0 THEN COALESCE(SUM(clicks), 0) / COALESCE(SUM(impressions), 0) ELSE 0 END',
        'cvr'         => 'CASE WHEN COALESCE(SUM(clicks), 0) > 0 THEN COALESCE(SUM(conversions), 0) / COALESCE(SUM(clicks), 0) ELSE 0 END',
        'roi'         => 'CASE WHEN COALESCE(SUM(cost), 0) > 0 THEN COALESCE(SUM(conversions), 0) / COALESCE(SUM(cost), 0) * 100 ELSE 0 END',
    ];

    /**
     * Evaluate a single alert rule. Returns an AlertLog if triggered, null otherwise.
     */
    public function evaluate(AlertRule $rule): ?AlertLog
    {
        if (!isset(self::METRIC_SQL[$rule->metric])) {
            return null;
        }

        $query = $this->buildMetricQuery($rule);
        $result = $query->first();

        if (!$result) {
            return null;
        }

        $currentValue = (float) ($result->metric_value ?? 0);

        if (!$this->compare($currentValue, (float) $rule->threshold, $rule->condition)) {
            return null;
        }

        // Prevent duplicate alerts within the same check interval
        $recent = AlertLog::where('rule_id', $rule->id)
            ->where('tenant_id', $rule->tenant_id)
            ->where('status', 'triggered')
            ->where('created_at', '>=', now()->subMinutes(max(1, (int) $rule->check_interval)))
            ->exists();

        if ($recent) {
            return null;
        }

        $log = AlertLog::create([
            'tenant_id'     => $rule->tenant_id,
            'rule_id'       => $rule->id,
            'rule_name'     => $rule->name,
            'metric'        => $rule->metric,
            'current_value' => $currentValue,
            'threshold'     => $rule->threshold,
            'condition'     => $rule->condition,
            'status'        => 'triggered',
            'extra'         => [
                'scope'      => $rule->scope,
                'platform'   => $rule->platform,
                'campaign_id' => $rule->campaign_id,
            ],
        ]);

        return $log;
    }

    /**
     * Build the query against report_metrics filtered by scope.
     */
    public function buildMetricQuery(AlertRule $rule): QueryBuilder
    {
        $selectRaw = self::METRIC_SQL[$rule->metric] . ' as metric_value';
        $query = DB::table('report_metrics')->selectRaw($selectRaw);

        // Scope filter
        switch ($rule->scope) {
            case 'platform':
                if ($rule->platform) {
                    $query->where('platform', $rule->platform);
                }
                break;
            case 'campaign':
                if ($rule->campaign_id) {
                    $query->where('campaign_id', $rule->campaign_id);
                }
                break;
            case 'tenant':
            default:
                $query->where('tenant_id', $rule->tenant_id);
                break;
        }

        // If scope is not tenant, still filter by tenant_id
        if ($rule->scope !== 'tenant') {
            $query->where('tenant_id', $rule->tenant_id);
        }

        // Today's data
        $query->where('date', date('Y-m-d'));

        return $query;
    }

    /**
     * Compare current value against threshold using the given condition.
     */
    protected function compare(float $currentValue, float $threshold, string $condition): bool
    {
        return match ($condition) {
            'gt'  => $currentValue > $threshold,
            'gte' => $currentValue >= $threshold,
            'lt'  => $currentValue < $threshold,
            'lte' => $currentValue <= $threshold,
            default => false,
        };
    }
}
