<?php
namespace plugin\ads_api\controller;

use plugin\ads_report\service\ReportExporter;
use Webman\Http\Request;
use Webman\Http\Response;
use app\support\ApiResponse;

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
        } catch (\Throwable $e) {
            return ApiResponse::error('导出失败: ' . $e->getMessage());
        }
    }
}
