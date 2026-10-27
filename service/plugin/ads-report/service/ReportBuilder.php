<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 报表构建。规则（团队约定）：SQL 仅做整数 SUM 聚合、分组与分页；
 * CTR/CVR/CPC/CPM/ROI 等派生比率指标一律 PHP 侧 bcmath 计算（bc_div/bc_round），
 * 不再在 selectRaw 里写 ROUND(CASE WHEN SUM(...) ...)。
 */
class ReportBuilder
{
    protected const SUM_SQL = [
        'cost'        => 'COALESCE(SUM(cost), 0)',
        'impressions' => 'COALESCE(SUM(impressions), 0)',
        'clicks'      => 'COALESCE(SUM(clicks), 0)',
        'conversions' => 'COALESCE(SUM(conversions), 0)',
    ];

    /**
     * 派生指标 → SQL 表达式。仅用于 ORDER BY 保持原排序语义，
     * 指标数值本身由 deriveMetric() 在 PHP 侧计算。
     */
    protected const DERIVED_SQL = [
        'ctr' => 'SUM(clicks)/SUM(impressions)',
        'cvr' => 'SUM(conversions)/SUM(clicks)',
        'cpc' => 'SUM(cost)/SUM(clicks)',
        'cpm' => 'SUM(cost)/SUM(impressions)*1000',
        'roi' => 'SUM(conversions)/SUM(cost)*100',
    ];

    public function buildCustom(int $tenantId, array $params): array
    {
        [$query, $dimensions, $metrics] = $this->queryFor($tenantId, $params);

        $perPage = min((int) ($params['per_page'] ?? 20), 100);
        $paginator = $query->paginate($perPage);

        $list = array_map(
            fn ($row) => $this->decorateRow((array) $row, $dimensions, $metrics),
            $paginator->items()
        );

        return [
            'list'       => $list,
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'per_page'    => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => (int) ceil($paginator->total() / $paginator->perPage()),
            ],
        ];
    }

    /**
     * 组装分组聚合查询：维度列 + 四个整数 SUM 列（派生指标所需底数全量取回，
     * 输出行由 decorateRow() 裁剪/计算）。
     *
     * @return array{0: object, 1: string[], 2: string[]} [query, groupCols, validMetrics]
     */
    protected function queryFor(int $tenantId, array $params): array
    {
        $dateStart  = $params['date_start'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateEnd    = $params['date_end']   ?? date('Y-m-d');
        $platform   = $params['platform']   ?? null;

        $dimensions = $this->normalizeList($params['dimensions'] ?? ['platform'], ['platform']);
        $metrics    = $this->normalizeList($params['metrics'] ?? ['cost', 'impressions', 'clicks'], ['cost', 'impressions', 'clicks']);

        $groupCols = $this->dimensionColumns($dimensions);
        $valid = [];
        foreach ($metrics as $m) {
            if (isset(self::SUM_SQL[$m]) || isset(self::DERIVED_SQL[$m])) {
                $valid[$m] = $m;
            }
        }
        $valid = array_values($valid);

        $query = DB::table('ads_report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd]);

        if ($platform) {
            $query->where('platform', $platform);
        }

        foreach ($groupCols as $col) {
            $query->select($col)->groupBy($col);
        }
        foreach (self::SUM_SQL as $alias => $raw) {
            $query->selectRaw("{$raw} as {$alias}");
        }

        // 与改造前一致：date 维度先行，随后按首个请求指标排序
        if (in_array('date', $groupCols)) {
            $query->orderBy('date');
        }
        $orderMetric = $valid[0] ?? 'cost';
        if (isset(self::DERIVED_SQL[$orderMetric])) {
            $query->orderByRaw(self::DERIVED_SQL[$orderMetric] . ' DESC');
        } else {
            $query->orderByDesc($orderMetric);
        }

        return [$query, $groupCols, $valid];
    }

    /**
     * 按请求顺序重建输出行：维度原样透传，基表指标取 SUM 值，
     * 派生指标调 deriveMetric() 走 bcmath。
     */
    protected function decorateRow(array $row, array $dimensions, array $metrics): array
    {
        $out = [];
        foreach ($dimensions as $col) {
            $out[$col] = $row[$col] ?? null;
        }
        foreach ($metrics as $m) {
            $out[$m] = isset(self::SUM_SQL[$m])
                ? ($row[$m] ?? 0)
                : $this->deriveMetric($m, $row);
        }
        return $out;
    }

    /**
     * 派生比率指标 PHP 侧 bcmath 计算（字符串返回，与 MySQL ROUND 的
     * DECIMAL 输出形态一致）。原 SQL 公式逐一平移：
     *   ctr = ROUND(clicks/impressions, 6)  cvr = ROUND(conversions/clicks, 6)
     *   cpc = ROUND(cost/clicks, 2)         cpm = ROUND(cost/impressions*1000, 2)
     *   roi = ROUND(conversions/cost*100, 2)
     */
    protected function deriveMetric(string $metric, array $row): string
    {
        $sum = fn (string $k) => (string) ((int) ($row[$k] ?? 0));
        return match ($metric) {
            'ctr' => bc_div($sum('clicks'), $sum('impressions'), 6),
            'cvr' => bc_div($sum('conversions'), $sum('clicks'), 6),
            'cpc' => bc_div($sum('cost'), $sum('clicks'), 2),
            'cpm' => bc_div(bcmul($sum('cost'), '1000'), $sum('impressions'), 2),
            'roi' => bc_div(bcmul($sum('conversions'), '100'), $sum('cost'), 2),
            default => '0',
        };
    }

    /**
     * 基表整数 SUM 列映射（派生指标不在此列——它们由 PHP 计算）。
     */
    protected function metricColumns(array $metrics): array
    {
        $result = [];
        foreach ($metrics as $m) {
            $m = trim($m);
            if (isset(self::SUM_SQL[$m])) {
                $result[$m] = self::SUM_SQL[$m];
            }
        }
        return $result;
    }

    protected function dimensionColumns(array $dimensions): array
    {
        return array_values(array_intersect($dimensions, ['platform', 'date', 'campaign_id', 'granularity']));
    }

    protected function normalizeList(array|string|null $input, array $default): array
    {
        if ($input === null || $input === '') {
            return $default;
        }
        if (is_string($input)) {
            $input = explode(',', $input);
        }
        return array_map('trim', (array) $input);
    }
}
