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
    /** 基表整数 SUM 列（SQL 仅做整数聚合；比率指标在 PHP 侧 bcmath 计算） */
    protected const METRIC_SUM_SQL = [
        'cost'        => 'COALESCE(SUM(cost), 0)',
        'impressions' => 'COALESCE(SUM(impressions), 0)',
        'clicks'      => 'COALESCE(SUM(clicks), 0)',
        'conversions' => 'COALESCE(SUM(conversions), 0)',
    ];

    /** 派生比率指标：分子/分母 SUM 列 + 放大系数（值由 derivedValue() bcmath 计算） */
    protected const METRIC_DERIVED = [
        'ctr' => ['numerator' => 'clicks',       'denominator' => 'impressions', 'factor' => 1],
        'cvr' => ['numerator' => 'conversions',  'denominator' => 'clicks',       'factor' => 1],
        'roi' => ['numerator' => 'conversions',  'denominator' => 'cost',         'factor' => 100],
    ];

    /**
     * Evaluate a single alert rule. Returns an AlertLog if triggered, null otherwise.
     */
    public function evaluate(AlertRule $rule): ?AlertLog
    {
        $metric  = $rule->metric;
        $derived = self::METRIC_DERIVED[$metric] ?? null;
        if (!isset(self::METRIC_SUM_SQL[$metric]) && $derived === null) {
            return null;
        }

        $query = $this->buildMetricQuery($rule, $metric, $derived);
        $result = $query->first();

        if (!$result) {
            return null;
        }

        $currentValue = $derived
            ? $this->derivedValue($result, $derived)
            : (float) ($result->metric_value ?? 0);

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
     *
     * @param string   $metric  规则指标名
     * @param ?array   $derived METRIC_DERIVED 派生定义；null 表示基表指标
     */
    public function buildMetricQuery(AlertRule $rule, string $metric, ?array $derived = null): QueryBuilder
    {
        $query = DB::table('ads_report_metrics');
        if ($derived) {
            // 只取分子/分母两个整数 SUM，比率由 PHP 侧 derivedValue() 计算
            foreach ([$derived['numerator'], $derived['denominator']] as $col) {
                $query->selectRaw("COALESCE(SUM({$col}), 0) as {$col}");
            }
        } else {
            $query->selectRaw(self::METRIC_SUM_SQL[$metric] . ' as metric_value');
        }

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
     * 派生比率指标 PHP 侧 bcmath 计算（原 SQL 为原始除法、无 ROUND，故不额外舍入）。
     * bcdiv 以 10 位截断后转 float，误差 ≤ 5e-11，对规则阈值（通常 ≤4 位小数）判定无影响；
     * 分母 ≤ 0 时返回 0.0（等价原 CASE ELSE 0）。
     */
    protected function derivedValue(object $result, array $def): float
    {
        $num = (string) ((int) ($result->{$def['numerator']} ?? 0));
        $den = (string) ((int) ($result->{$def['denominator']} ?? 0));
        if (bccomp($den, '0') <= 0) {
            return 0.0;
        }
        return (float) bcdiv(bcmul($num, (string) $def['factor']), $den, 10);
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
