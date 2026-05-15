<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class ReportBuilder
{
    public function buildCustom(int $tenantId, array $params): array
    {
        $dateStart  = $params['date_start']  ?? date('Y-m-d', strtotime('-7 days'));
        $dateEnd    = $params['date_end']    ?? date('Y-m-d');
        $dimensions = $params['dimensions']  ?? ['platform'];
        $metrics    = $params['metrics']     ?? ['cost', 'impressions', 'clicks'];
        $platform   = $params['platform']    ?? null;

        $metricCols = $this->metricColumns($metrics);
        $groupCols  = $this->dimensionColumns($dimensions);

        $query = DB::table('erik_report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd]);

        if ($platform) {
            $query->where('platform', $platform);
        }

        foreach ($groupCols as $col) {
            $query->groupBy($col)->select($col);
        }
        foreach ($metricCols as $alias => $raw) {
            $query->selectRaw("{$raw} as {$alias}");
        }
        if (in_array('date', $dimensions)) {
            $query->orderBy('date');
        }
        $query->orderByDesc(array_keys($metricCols)[0] ?? 'cost');

        $perPage = min((int) ($params['per_page'] ?? 20), 100);
        $paginator = $query->paginate($perPage);

        return [
            'list'       => $paginator->items(),
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'per_page'    => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => (int) ceil($paginator->total() / $paginator->perPage()),
            ],
        ];
    }

    protected function metricColumns(array $metrics): array
    {
        $map = [
            'cost'         => 'COALESCE(SUM(cost), 0)',
            'impressions'  => 'COALESCE(SUM(impressions), 0)',
            'clicks'       => 'COALESCE(SUM(clicks), 0)',
            'conversions'  => 'COALESCE(SUM(conversions), 0)',
            'ctr'          => 'CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(clicks)/SUM(impressions), 6) ELSE 0 END',
            'cvr'          => 'CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(conversions)/SUM(clicks), 6) ELSE 0 END',
            'cpc'          => 'CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(cost)/SUM(clicks), 2) ELSE 0 END',
            'cpm'          => 'CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(cost)/SUM(impressions)*1000, 2) ELSE 0 END',
            'roi'          => 'CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(conversions)/SUM(cost)*100, 2) ELSE 0 END',
        ];
        $result = [];
        foreach ($metrics as $m) {
            if (isset($map[$m])) $result[$m] = $map[$m];
        }
        return $result;
    }

    protected function dimensionColumns(array $dimensions): array
    {
        return array_values(array_intersect($dimensions, ['platform', 'date', 'campaign_id', 'granularity']));
    }
}
