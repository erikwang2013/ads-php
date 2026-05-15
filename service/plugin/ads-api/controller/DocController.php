<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller;

use Webman\Http\Request;
use app\support\ApiResponse;

class DocController
{
    public function index(): \Webman\Http\Response
    {
        $routes = [
            ['method' => 'POST', 'path' => '/api/v1/auth/login', 'desc' => '登录获取 JWT Token', 'auth' => false, 'body' => '{"username":"admin","password":"admin123"}'],
            ['method' => 'GET',  'path' => '/api/v1/auth/me', 'desc' => '当前用户信息', 'auth' => true],

            ['method' => 'GET',  'path' => '/api/v1/platforms', 'desc' => '支持的广告平台列表', 'auth' => true],
            ['method' => 'GET',  'path' => '/api/v1/platforms/:code/oauth-url', 'desc' => '获取平台 OAuth 授权 URL', 'auth' => true, 'params' => '?redirect_uri=...'],
            ['method' => 'POST', 'path' => '/api/v1/platforms/:code/callback', 'desc' => 'OAuth 回调处理', 'auth' => true, 'body' => '{"state":"...","code":"..."}'],

            ['method' => 'GET',  'path' => '/api/v1/accounts', 'desc' => '已绑定的平台账户列表', 'auth' => true, 'params' => '?platform=juliang&page=1&per_page=20'],
            ['method' => 'GET',  'path' => '/api/v1/accounts/:id', 'desc' => '账户详情', 'auth' => true],
            ['method' => 'DELETE', 'path' => '/api/v1/accounts/:id', 'desc' => '解绑账户', 'auth' => true],
            ['method' => 'POST', 'path' => '/api/v1/accounts/:id/sync', 'desc' => '手动触发数据同步', 'auth' => true],

            ['method' => 'GET',  'path' => '/api/v1/campaigns', 'desc' => '广告计划列表', 'auth' => true, 'params' => '?platform=juliang&status=enabled&keyword=test&sort=cost&page=1&per_page=20'],
            ['method' => 'POST', 'path' => '/api/v1/campaigns', 'desc' => '创建广告计划', 'auth' => true, 'body' => '{"platform":"juliang","platform_account_id":"hashids...","name":"test","daily_budget":20000}'],
            ['method' => 'GET',  'path' => '/api/v1/campaigns/:id', 'desc' => '计划详情（含今日数据）', 'auth' => true],
            ['method' => 'PUT',  'path' => '/api/v1/campaigns/:id', 'desc' => '更新计划', 'auth' => true],
            ['method' => 'POST', 'path' => '/api/v1/campaigns/:id/toggle', 'desc' => '启停计划', 'auth' => true, 'body' => '{"enabled":false}'],

            ['method' => 'GET',  'path' => '/api/v1/reports/summary', 'desc' => '仪表盘汇总数据', 'auth' => true, 'params' => '?date_start=2026-05-01&date_end=2026-05-16'],
            ['method' => 'GET',  'path' => '/api/v1/reports/custom', 'desc' => '自定义多维度报表', 'auth' => true, 'params' => '?dimensions[]=platform&dimensions[]=date&metrics[]=cost&metrics[]=clicks&date_start=2026-05-01&date_end=2026-05-16'],
            ['method' => 'GET',  'path' => '/api/v1/reports/export', 'desc' => '导出报表 CSV/Excel', 'auth' => true, 'params' => '?format=csv&date_start=2026-05-01&date_end=2026-05-16&metrics[]=cost&metrics[]=impressions'],
            ['method' => 'GET',  'path' => '/api/v1/reports/export-dashboard', 'desc' => '导出仪表盘 PDF', 'auth' => true, 'params' => '?date_start=2026-05-01&date_end=2026-05-16'],

            ['method' => 'GET',  'path' => '/api/v1/alerts/rules', 'desc' => '告警规则列表', 'auth' => true],
            ['method' => 'POST', 'path' => '/api/v1/alerts/rules', 'desc' => '创建告警规则', 'auth' => true, 'body' => '{"name":"花费超限","metric":"cost","condition":"gt","threshold":100000,"scope":"tenant","channels":["web"]}'],
            ['method' => 'PUT',  'path' => '/api/v1/alerts/rules/:id', 'desc' => '更新告警规则', 'auth' => true],
            ['method' => 'DELETE', 'path' => '/api/v1/alerts/rules/:id', 'desc' => '删除告警规则', 'auth' => true],
            ['method' => 'GET',  'path' => '/api/v1/alerts/logs', 'desc' => '告警记录列表', 'auth' => true, 'params' => '?status=triggered&page=1&per_page=20'],
            ['method' => 'POST', 'path' => '/api/v1/alerts/logs/:id/acknowledge', 'desc' => '确认告警', 'auth' => true],
            ['method' => 'GET',  'path' => '/api/v1/alerts/unread-count', 'desc' => '未读告警数量', 'auth' => true],
        ];

        $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>API 文档</title>';
        $html .= '<style>body{font-family:-apple-system,sans-serif;max-width:960px;margin:0 auto;padding:20px;background:#f8f9fa}';
        $html .= 'h1{color:#1a1a2e}h2{color:#16213e;border-bottom:2px solid #e0e0e0;padding-bottom:8px}';
        $html .= '.endpoint{background:#fff;border-radius:8px;padding:12px 16px;margin:8px 0;box-shadow:0 1px 3px rgba(0,0,0,0.08)}';
        $html .= '.method{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:700;margin-right:8px;min-width:48px;text-align:center}';
        $html .= '.GET{background:#d4edda;color:#155724}.POST{background:#cce5ff;color:#004085}';
        $html .= '.PUT{background:#fff3cd;color:#856404}.DELETE{background:#f8d7da;color:#721c24}';
        $html .= '.path{font-family:monospace;font-size:14px}.desc{color:#666;font-size:14px;margin-left:16px}';
        $html .= '.params,.body{font-family:monospace;font-size:12px;color:#888;margin-left:16px}';
        $html .= '.auth{font-size:11px;color:#28a745;margin-left:8px}';
        $html .= 'footer{text-align:center;color:#aaa;margin-top:40px;font-size:12px}</style></head><body>';
        $html .= '<h1>Ads Platform API v1</h1><p>Base URL: <code>/api/v1</code></p>';

        $currentGroup = '';
        foreach ($routes as $r) {
            $group = explode('/', trim($r['path'], '/'))[2] ?? '';
            if ($group !== $currentGroup) {
                $currentGroup = $group;
                $html .= '<h2>' . ucfirst($group) . '</h2>';
            }
            $lock = $r['auth'] ? '<span class="auth">🔒</span>' : '';
            $html .= '<div class="endpoint">';
            $html .= '<span class="method ' . $r['method'] . '">' . $r['method'] . '</span>';
            $html .= '<span class="path">' . $r['path'] . '</span>' . $lock;
            $html .= '<span class="desc">' . $r['desc'] . '</span>';
            if (isset($r['params'])) $html .= '<div class="params">📎 ' . $r['params'] . '</div>';
            if (isset($r['body'])) $html .= '<div class="body">📦 ' . $r['body'] . '</div>';
            $html .= '</div>';
        }
        $html .= '<footer>Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz</footer></body></html>';

        return new \Webman\Http\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
