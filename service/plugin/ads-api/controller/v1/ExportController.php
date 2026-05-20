<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_report\service\ReportExporter;
use plugin\ads_report\service\PdfExporter;
use Webman\Http\Request;
use Webman\Http\Response;
use app\support\ApiResponse;
use Throwable;


use \erik\support\ControllerTrait;

class ExportController
{
    public function export(Request $request): Response
    {
        $format   = $request->get('format', 'csv'); // csv, excel
        $tenantId = $request->tenantId ?? 1;

        $exporter = new ReportExporter();

        try {
            $filePath = $format === 'csv'
                ? $exporter->exportCsv($tenantId, $request->all())
                : $exporter->exportExcel($tenantId, $request->all());

            $ext      = $format === 'csv' ? 'csv' : 'xls';
            $filename = 'report_' . date('YmdHis') . '.' . $ext;

            // Read file into response
            return (new Response())->file($filePath, $filename);
        } catch (Throwable $e) {
            return ApiResponse::error('导出失败: ' . $e->getMessage());
        }
    }

    public function exportDashboard(Request $request): Response
    {
        $tenantId = $request->tenantId ?? 1;
        $format   = $request->get('format', 'pdf');

        if ($format === 'pdf') {
            $exporter = new PdfExporter();
            $filePath = $exporter->exportDashboardPdf($tenantId, $request->all());
            return (new Response())->file($filePath, 'dashboard_' . date('YmdHis') . '.pdf');
        }

        return ApiResponse::error('Unsupported format');
    }
}
