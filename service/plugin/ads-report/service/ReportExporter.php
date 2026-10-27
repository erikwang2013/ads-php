<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_report\service;

/**
 * 报表导出。查询/聚合/派生比率（bcmath PHP 侧）复用 ReportBuilder；
 * 本类只负责表头翻译与 CSV / Excel-HTML 输出。
 */
class ReportExporter extends ReportBuilder
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
        [$query, $dimensions, $metrics] = $this->queryFor($tenantId, $params);

        return $query->get()->map(function ($row) use ($dimensions, $metrics) {
            return $this->decorateRow((array) $row, $dimensions, $metrics);
        })->toArray();
    }

    /**
     * Build header keys from params when data is empty.
     */
    protected function headerKeys(array $params): array
    {
        $dimKeys = $this->dimensionColumns(
            $this->normalizeList($params['dimensions'] ?? null, ['platform'])
        );

        $metrics = $this->normalizeList(
            $params['metrics'] ?? null,
            ['cost', 'impressions', 'clicks']
        );

        $valid = [];
        foreach ($metrics as $m) {
            if (isset(self::SUM_SQL[$m]) || isset(self::DERIVED_SQL[$m])) {
                $valid[$m] = $m;
            }
        }

        return array_merge($dimKeys, array_values($valid));
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
}
