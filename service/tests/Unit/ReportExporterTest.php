<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * ReportExporter 测试：表头翻译/指标列/维度列（纯逻辑）；
 * SQLite 上验证 CSV（BOM + 中文表头）与 Excel HTML 导出文件内容。
 */

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_report\service\ReportExporter;

/** 暴露 protected 纯逻辑方法供断言 */
class ExposableReportExporter extends ReportExporter
{
    public function exposeHeaderKeys(array $params): array { return $this->headerKeys($params); }
    public function exposeTranslateHeaders(array $keys): array { return $this->translateHeaders($keys); }
    public function exposeMetricColumns(array $metrics): array { return $this->metricColumns($metrics); }
    public function exposeDimensionColumns(array $dimensions): array { return $this->dimensionColumns($dimensions); }
}

class ReportExporterTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->exec('CREATE TABLE ads_report_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT,
            campaign_id INT, date TEXT, cost INT, impressions INT, clicks INT, conversions INT)');
    }

    public function testTranslateHeadersMapsChineseLabels(): void
    {
        $exporter = new ExposableReportExporter();
        $this->assertSame(
            ['平台', '日期', '花费', 'ROI'],
            $exporter->exposeTranslateHeaders(['platform', 'date', 'cost', 'roi'])
        );
        // 未知列原样返回
        $this->assertSame(['custom_col'], $exporter->exposeTranslateHeaders(['custom_col']));
    }

    public function testMetricColumnsFiltersUnknownMetrics(): void
    {
        $exporter = new ExposableReportExporter();
        $cols = $exporter->exposeMetricColumns(['cost', 'bogus', ' ctr ', 'roi']);
        $this->assertSame(['cost', 'ctr', 'roi'], array_keys($cols));
    }

    public function testDimensionColumnsIntersectsWhitelist(): void
    {
        $exporter = new ExposableReportExporter();
        $this->assertSame(
            ['platform', 'date', 'campaign_id', 'granularity'],
            $exporter->exposeDimensionColumns(['platform', 'date', 'campaign_id', 'granularity', 'evil'])
        );
    }

    public function testHeaderKeysFallbackWhenNoData(): void
    {
        $exporter = new ExposableReportExporter();
        $this->assertSame(
            ['platform', 'cost', 'impressions', 'clicks'],
            $exporter->exposeHeaderKeys(['dimensions' => ['platform'], 'metrics' => ['cost', 'impressions', 'clicks']])
        );
    }

    public function testExportCsvWritesBomAndChineseHeaders(): void
    {
        $path = (new ReportExporter())->exportCsv(1, [
            'date_start' => '2026-08-01', 'date_end' => '2026-08-27',
            'dimensions' => ['platform'], 'metrics' => ['cost', 'clicks'],
        ]);

        $content = file_get_contents($path);
        unlink($path);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'CSV should start with UTF-8 BOM');
        $this->assertStringContainsString('平台', $content);
        $this->assertStringContainsString('花费', $content);
    }

    public function testExportCsvIncludesDataRows(): void
    {
        DB::table('ads_report_metrics')->insert([
            'tenant_id' => 1, 'platform' => 'juliang', 'date' => '2026-08-20', 'cost' => 1234, 'clicks' => 56,
        ]);

        $path = (new ReportExporter())->exportCsv(1, [
            'date_start' => '2026-08-01', 'date_end' => '2026-08-27',
            'dimensions' => ['platform'], 'metrics' => ['cost', 'clicks'],
        ]);

        $content = file_get_contents($path);
        unlink($path);

        $this->assertStringContainsString('juliang', $content);
        $this->assertStringContainsString('1234', $content);
    }

    public function testExportExcelBuildsHtmlTable(): void
    {
        DB::table('ads_report_metrics')->insert([
            'tenant_id' => 1, 'platform' => 'juliang', 'date' => '2026-08-20', 'cost' => 100, 'clicks' => 10,
        ]);

        $path = (new ReportExporter())->exportExcel(1, [
            'date_start' => '2026-08-01', 'date_end' => '2026-08-27',
            'dimensions' => ['platform'], 'metrics' => ['cost'],
        ]);

        $html = file_get_contents($path);
        unlink($path);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('<th>平台</th>', $html);
        $this->assertStringContainsString('<th>花费</th>', $html);
        $this->assertStringContainsString('juliang', $html);
    }
}
