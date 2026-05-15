<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class ReportExporter
{
    /**
     * Export report data as CSV file.
     *
     * @param int   $tenantId
     * @param array $params   Keys: date_start, date_end, dimensions, metrics, platform
     * @return string File path to generated CSV
     */
    public function exportCsv(int $tenantId, array $params): string
    {
        $data = $this->fetchAllData($tenantId, $params);

        $filePath = '/tmp/report_' . $tenantId . '_' . date('YmdHis') . '_' . uniqid() . '.csv';
        $fp = fopen($filePath, 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($fp, "\xEF\xBB\xBF");

        // Collect all column keys from the first row
        $headers = !empty($data) ? array_keys(reset($data)) : $this->headerKeys($params);
        fputcsv($fp, $this->translateHeaders($headers));

        foreach ($data as $row) {
            fputcsv($fp, array_values($row));
        }

        fclose($fp);
        return $filePath;
    }

    /**
     * Export report data as Excel-compatible HTML table (.xls file).
     *
     * @param int   $tenantId
     * @param array $params   Keys: date_start, date_end, dimensions, metrics, platform
     * @return string File path to generated .xls file
     */
    public function exportExcel(int $tenantId, array $params): string
    {
        $data = $this->fetchAllData($tenantId, $params);

        $headers = !empty($data) ? array_keys(reset($data)) : $this->headerKeys($params);
        $translated = $this->translateHeaders($headers);

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        $html .= '<body><table border="1">';

        // Header row
        $html .= '<tr>';
        foreach ($translated as $header) {
            $html .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr>';

        // Data rows
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                if (is_numeric($value) && is_float($value + 0)) {
                    $html .= '<td>' . number_format((float)$value, 4) . '</td>';
                } else {
                    $html .= '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        $filePath = '/tmp/report_' . $tenantId . '_' . date('YmdHis') . '_' . uniqid() . '.xls';
        file_put_contents($filePath, $html);

        return $filePath;
    }

    /**
     * Fetch all matching report data without pagination.
     *
     * @param int   $tenantId
     * @param array $params
     * @return array
     */
    protected function fetchAllData(int $tenantId, array $params): array
    {
        $dateStart  = $params['date_start']  ?? date('Y-m-d', strtotime('-7 days'));
        $dateEnd    = $params['date_end']    ?? date('Y-m-d');
        $dimensions = $params['dimensions']  ?? ['platform'];
        $metrics    = $params['metrics']     ?? ['cost', 'impressions', 'clicks'];
        $platform   = $params['platform']    ?? null;

        // Normalize arrays from query string (e.g. "dimensions[]=platform&dimensions[]=date")
        if (is_string($dimensions)) {
            $dimensions = explode(',', $dimensions);
        }
        if (is_string($metrics)) {
            $metrics = explode(',', $metrics);
        }

        $metricCols = $this->metricColumns($metrics);
        $groupCols  = $this->dimensionColumns($dimensions);

        $query = DB::table('erik_report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd]);

        if ($platform) {
            $query->where('platform', $platform);
        }

        foreach ($groupCols as $col) {
            $query->select($col)->groupBy($col);
        }
        foreach ($metricCols as $alias => $raw) {
            $query->selectRaw("{$raw} as {$alias}");
        }
        if (in_array('date', $dimensions)) {
            $query->orderBy('date');
        }
        $query->orderByDesc(array_keys($metricCols)[0] ?? 'cost');

        return $query->get()->map(function ($row) {
            return (array)$row;
        })->toArray();
    }

    /**
     * Build header keys from params when data is empty.
     */
    protected function headerKeys(array $params): array
    {
        $dimensions = $params['dimensions'] ?? ['platform'];
        $metrics    = $params['metrics']    ?? ['cost', 'impressions', 'clicks'];

        if (is_string($dimensions)) {
            $dimensions = explode(',', $dimensions);
        }
        if (is_string($metrics)) {
            $metrics = explode(',', $metrics);
        }

        $dimKeys = $this->dimensionColumns($dimensions);
        $validMetrics = array_keys($this->metricColumns($metrics));

        return array_merge($dimKeys, $validMetrics);
    }

    /**
     * Translate field keys to Chinese labels.
     */
    protected function translateHeaders(array $keys): array
    {
        $map = [
            'platform'     => '平台',
            'date'         => '日期',
            'campaign_id'  => '计划ID',
            'granularity'  => '粒度',
            'cost'         => '花费',
            'impressions'  => '展示量',
            'clicks'       => '点击量',
            'conversions'  => '转化量',
            'ctr'          => '点击率',
            'cvr'          => '转化率',
            'cpc'          => '点击均价',
            'cpm'          => '千次展示价',
            'roi'          => 'ROI',
        ];

        return array_map(function ($key) use ($map) {
            return $map[$key] ?? $key;
        }, $keys);
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
            $m = trim($m);
            if (isset($map[$m])) {
                $result[$m] = $map[$m];
            }
        }
        return $result;
    }

    protected function dimensionColumns(array $dimensions): array
    {
        return array_values(array_intersect($dimensions, ['platform', 'date', 'campaign_id', 'granularity']));
    }
}
