<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class PdfExporter
{
    /**
     * Generate a dashboard PDF report from report_metrics data.
     * Produces an HTML page with inline CSS that browsers can print to PDF.
     *
     * @param int   $tenantId
     * @param array $params   Keys: date_start, date_end
     * @return string File path to generated HTML file
     */
    public function exportDashboardPdf(int $tenantId, array $params): string
    {
        $dateStart = $params['date_start'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateEnd   = $params['date_end']   ?? date('Y-m-d');

        // --- Fetch summary overview ---
        $overview = (array) DB::table('ads_report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as total_conversions')
            ->first();
        $overview = $this->hydrateOverview($overview);

        // --- Fetch platform comparison ---
        $byPlatform = DB::table('ads_report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->groupBy('platform')
            ->select('platform')
            ->selectRaw('COALESCE(SUM(cost), 0) as cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as conversions')
            ->orderByDesc('cost')
            ->get()
            ->map(function ($row) {
                $row = (array) $row;
                $row['ctr'] = $this->divMetric($row, 'clicks', 'impressions', 100);
                $row['cvr'] = $this->divMetric($row, 'conversions', 'clicks', 100);
                $row['cpc'] = $this->divMetric($row, 'cost', 'clicks');
                return $row;
            })
            ->toArray();

        // --- Fetch daily trend ---
        $daily = DB::table('ads_report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->groupBy('date')
            ->orderBy('date')
            ->select('date')
            ->selectRaw('COALESCE(SUM(cost), 0) as cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as conversions')
            ->get()
            ->toArray();

        $html = $this->buildHtml($overview, $byPlatform, $daily, $dateStart, $dateEnd);

        $filePath = '/tmp/dashboard_' . $tenantId . '_' . date('YmdHis') . '_' . uniqid() . '.html';
        file_put_contents($filePath, $html);

        return $filePath;
    }

    /**
     * Overview 派生比率 PHP 侧 bcmath 计算（SQL 只回整数 SUM）。
     * 原 SQL 公式平移：
     *   avg_ctr = ROUND(clicks/impressions*100, 2)   avg_cvr = ROUND(conversions/clicks*100, 2)
     *   avg_cpc = ROUND(cost/clicks, 2)（分/点击，展示时 bc_money ÷100 → 元）
     *   avg_cpa = ROUND(cost/conversions/100, 2)（元/转化）
     */
    protected function hydrateOverview(array $overview): array
    {
        $overview['avg_ctr'] = $this->divMetric($overview, 'total_clicks', 'total_impressions', 100);
        $overview['avg_cvr'] = $this->divMetric($overview, 'total_conversions', 'total_clicks', 100);
        $overview['avg_cpc'] = $this->divMetric($overview, 'total_cost', 'total_clicks');
        $overview['avg_cpa'] = $this->divMetric($overview, 'total_cost', 'total_conversions', 1, 100);
        return $overview;
    }

    /**
     * 派生比率 bcmath 除法（num×numFactor / den×denFactor，两位小数）。
     * 分母 ≤ 0 由 bc_div 守卫返回 "0.00"（等价原 SQL CASE ELSE 0）。
     */
    protected function divMetric(array $row, string $numKey, string $denKey, int $numFactor = 1, int $denFactor = 1): string
    {
        $num = (string) ((int) ($row[$numKey] ?? 0));
        $den = (string) ((int) ($row[$denKey] ?? 0));
        return bc_div(bcmul($num, (string) $numFactor), bcmul($den, (string) $denFactor), 2);
    }

    /**
     * Build the printable HTML report.
     */
    protected function buildHtml(array $overview, array $byPlatform, array $daily, string $dateStart, string $dateEnd): string
    {
        $overviewHtml = $this->renderOverviewTable($overview);
        $platformHtml = $this->renderPlatformTable($byPlatform);
        $dailyHtml    = $this->renderDailyTable($daily);

        $title = '广告数据报表';
        $subtitle = "日期范围: {$dateStart} ~ {$dateEnd}";

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
<style>
  @page { size: A4 landscape; margin: 15mm; }
  body {
    font-family: "Microsoft YaHei", "SimHei", "PingFang SC", "Hiragino Sans GB", "Noto Sans SC", sans-serif;
    color: #303133;
    font-size: 13px;
    line-height: 1.5;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .header { text-align: center; margin-bottom: 24px; }
  .header h1 { font-size: 22px; margin: 0 0 6px; color: #1d1d1f; }
  .header .subtitle { font-size: 13px; color: #909399; }
  .section { margin-bottom: 24px; }
  .section h2 { font-size: 16px; margin: 0 0 12px; padding-bottom: 6px; border-bottom: 2px solid #409EFF; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #e4e7ed; padding: 8px 10px; text-align: center; }
  th { background-color: #f5f7fa; font-weight: 600; white-space: nowrap; }
  tr:nth-child(even) td { background-color: #fafafa; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }
  .highlight { color: #409EFF; font-weight: 600; }
  .metric-grid { display: flex; flex-wrap: wrap; gap: 12px; }
  .metric-card {
    flex: 1 1 calc(25% - 12px); min-width: 150px;
    border: 1px solid #e4e7ed; border-radius: 6px; padding: 14px 16px;
    background: #fafbfc;
  }
  .metric-card .label { font-size: 12px; color: #909399; margin-bottom: 4px; }
  .metric-card .value { font-size: 22px; font-weight: 700; color: #303133; }
  .footer { text-align: center; font-size: 11px; color: #c0c4cc; margin-top: 30px; padding-top: 12px; border-top: 1px solid #ebeef5; }
  @media print {
    .section { page-break-inside: avoid; }
  }
</style>
</head>
<body>

<div class="header">
  <h1>{$title}</h1>
  <p class="subtitle">{$subtitle}</p>
</div>

<div class="section">
  <h2>核心指标汇总</h2>
  {$overviewHtml}
</div>

<div class="section">
  <h2>平台对比</h2>
  {$platformHtml}
</div>

<div class="section">
  <h2>每日趋势</h2>
  {$dailyHtml}
</div>

<div class="footer">
  由 Erik Dashboard 自动生成 &mdash; {$dateStart} ~ {$dateEnd}
</div>

</body>
</html>
HTML;
    }

    /**
     * Render overview metric cards and table.
     */
    protected function renderOverviewTable(array $overview): string
    {
        $cost        = bc_money($overview['total_cost'] ?? 0);
        $impressions = number_format($overview['total_impressions'] ?? 0);
        $clicks      = number_format($overview['total_clicks'] ?? 0);
        $conversions = number_format($overview['total_conversions'] ?? 0);
        $ctr         = number_format((float)($overview['avg_ctr'] ?? 0), 2) . '%';
        $cvr         = number_format((float)($overview['avg_cvr'] ?? 0), 2) . '%';
        $cpc         = bc_money($overview['avg_cpc'] ?? 0);
        $cpa         = number_format((float)($overview['avg_cpa'] ?? 0), 2);

        return <<<HTML
<div class="metric-grid">
  <div class="metric-card"><div class="label">总花费</div><div class="value highlight">¥{$cost}</div></div>
  <div class="metric-card"><div class="label">展示量</div><div class="value">{$impressions}</div></div>
  <div class="metric-card"><div class="label">点击量</div><div class="value">{$clicks}</div></div>
  <div class="metric-card"><div class="label">转化量</div><div class="value">{$conversions}</div></div>
  <div class="metric-card"><div class="label">点击率 (CTR)</div><div class="value">{$ctr}</div></div>
  <div class="metric-card"><div class="label">转化率 (CVR)</div><div class="value">{$cvr}</div></div>
  <div class="metric-card"><div class="label">点击均价 (CPC)</div><div class="value">¥{$cpc}</div></div>
  <div class="metric-card"><div class="label">平均CPA</div><div class="value">¥{$cpa}</div></div>
</div>
HTML;
    }

    /**
     * Render platform comparison table.
     */
    protected function renderPlatformTable(array $byPlatform): string
    {
        if (empty($byPlatform)) {
            return '<p style="color:#909399;">暂无数据</p>';
        }

        $rows = '';
        foreach ($byPlatform as $row) {
            $row = (array) $row;
            $platform = htmlspecialchars($row['platform'] ?? '', ENT_QUOTES, 'UTF-8');
            $cost     = bc_money($row['cost'] ?? 0);
            $imp      = number_format($row['impressions'] ?? 0);
            $clk      = number_format($row['clicks'] ?? 0);
            $conv     = number_format($row['conversions'] ?? 0);
            $ctr      = number_format((float)($row['ctr'] ?? 0), 2) . '%';
            $cvr      = number_format((float)($row['cvr'] ?? 0), 2) . '%';
            $cpc      = bc_money($row['cpc'] ?? 0);
            $rows    .= "<tr>
                <td class=\"text-left\">{$platform}</td>
                <td class=\"text-right\">¥{$cost}</td>
                <td class=\"text-right\">{$imp}</td>
                <td class=\"text-right\">{$clk}</td>
                <td class=\"text-right\">{$conv}</td>
                <td class=\"text-right\">{$ctr}</td>
                <td class=\"text-right\">{$cvr}</td>
                <td class=\"text-right\">¥{$cpc}</td>
            </tr>";
        }

        return <<<HTML
<table>
  <thead>
    <tr>
      <th>平台</th>
      <th>花费</th>
      <th>展示量</th>
      <th>点击量</th>
      <th>转化量</th>
      <th>CTR</th>
      <th>CVR</th>
      <th>CPC</th>
    </tr>
  </thead>
  <tbody>{$rows}</tbody>
</table>
HTML;
    }

    /**
     * Render daily trend table.
     */
    protected function renderDailyTable(array $daily): string
    {
        if (empty($daily)) {
            return '<p style="color:#909399;">暂无数据</p>';
        }

        $rows = '';
        foreach ($daily as $row) {
            $row  = (array) $row;
            $date = htmlspecialchars($row['date'] ?? '', ENT_QUOTES, 'UTF-8');
            $cost = bc_money($row['cost'] ?? 0);
            $imp  = number_format($row['impressions'] ?? 0);
            $clk  = number_format($row['clicks'] ?? 0);
            $conv = number_format($row['conversions'] ?? 0);
            $rows .= "<tr>
                <td>{$date}</td>
                <td class=\"text-right\">¥{$cost}</td>
                <td class=\"text-right\">{$imp}</td>
                <td class=\"text-right\">{$clk}</td>
                <td class=\"text-right\">{$conv}</td>
            </tr>";
        }

        return <<<HTML
<table>
  <thead>
    <tr>
      <th>日期</th>
      <th>花费</th>
      <th>展示量</th>
      <th>点击量</th>
      <th>转化量</th>
    </tr>
  </thead>
  <tbody>{$rows}</tbody>
</table>
HTML;
    }
}
