<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;

class DocController
{
    public function index(): \Webman\Http\Response
    {
        $sections = [
            '系统' => [
                ['GET', '/health', '健康检查（DB + Redis 连通性）', false, ''],
                ['GET', '/ping', '轻量探活', false, ''],
                ['GET', '/docs', 'API 文档（本页）', false, ''],
                ['GET', '/api/captcha/generate', '生成滑块验证码', false, ''],
                ['POST', '/api/captcha/verify', '验证滑块偏移量', false, '{"captcha_token":"...","captcha_offset":120}'],
            ],
            '认证' => [
                ['POST', '/api/auth/login', '登录获取 JWT Token', false, '{"username":"admin","password":"******"}'],
                ['GET', '/api/auth/me', '当前用户信息', true, ''],
                ['POST', '/api/auth/refresh', '刷新 JWT Token（旧 Token 自动黑名单）', true, ''],
            ],
            '平台 & 账户' => [
                ['GET', '/api/platforms', '29 个适配平台列表（缓存 1h）', false, ''],
                ['GET', '/api/platforms/:code/oauth-url', '获取平台 OAuth 授权 URL', true, '?redirect_uri=...'],
                ['POST', '/api/platforms/:code/callback', 'OAuth 回调处理', true, '{"state":"...","code":"..."}'],
                ['GET', '/api/accounts', '已绑定账户列表（缓存 5min）', true, '?platform=juliang&page=1&per_page=20'],
                ['GET', '/api/accounts/:id', '账户详情（缓存 5min）', true, ''],
                ['DELETE', '/api/accounts/:id', '解绑账户', true, ''],
                ['POST', '/api/accounts/:id/sync', '手动触发数据同步', true, ''],
            ],
            '广告计划' => [
                ['GET', '/api/campaigns', '计划列表（筛选/排序/分页）', true, '?platform=juliang&status=enabled&sort=cost&page=1&per_page=20'],
                ['POST', '/api/campaigns', '创建广告计划', true, '{"platform":"juliang","platform_account_id":"...","name":"测试计划","daily_budget":20000}'],
                ['GET', '/api/campaigns/:id', '计划详情（含今日指标）', true, ''],
                ['PUT', '/api/campaigns/:id', '更新计划', true, '{"name":"新名称"}'],
                ['POST', '/api/campaigns/:id/toggle', '启停计划', true, '{"enabled":false}'],
                ['POST', '/api/campaigns/batch/toggle', '批量启停', true, '{"ids":[1,2,3],"enabled":false}'],
            ],
            '广告组' => [
                ['GET', '/api/ad-groups', '广告组列表', true, '?campaign_id=1&status=enabled&page=1&per_page=20'],
                ['POST', '/api/ad-groups', '创建广告组（支持定位模板）', true, '{"campaign_id":1,"name":"测试组","bid_amount":100,"bid_type":"cpc","targeting_template_id":1}'],
                ['GET', '/api/ad-groups/:id', '广告组详情', true, ''],
                ['PUT', '/api/ad-groups/:id', '更新广告组', true, '{"name":"新名称","targeting":{...}}'],
                ['POST', '/api/ad-groups/:id/toggle', '启停广告组', true, '{"enabled":false}'],
            ],
            '创意' => [
                ['GET', '/api/creatives', '创意列表', true, '?ad_group_id=1&media_type=image&page=1&per_page=20'],
                ['GET', '/api/creatives/:id', '创意详情', true, ''],
            ],
            '报表' => [
                ['GET', '/api/reports/summary', '仪表盘汇总（缓存 5min）', true, '?date_start=2026-05-01&date_end=2026-05-21'],
                ['GET', '/api/reports/custom', '自定义多维度报表', true, '?dimensions[]=platform&metrics[]=cost&metrics[]=clicks&date_start=2026-05-01&date_end=2026-05-21'],
                ['GET', '/api/reports/export', '导出 CSV/Excel', true, '?format=csv&date_start=2026-05-01&date_end=2026-05-21'],
                ['GET', '/api/reports/export-dashboard', '导出仪表盘 PDF', true, '?date_start=2026-05-01&date_end=2026-05-21'],
            ],
            '告警' => [
                ['GET', '/api/alerts/rules', '告警规则列表（缓存 2min）', true, ''],
                ['POST', '/api/alerts/rules', '创建告警规则', true, '{"name":"花费超限","metric":"cost","condition":"gt","threshold":100000}'],
                ['PUT', '/api/alerts/rules/:id', '更新告警规则', true, ''],
                ['DELETE', '/api/alerts/rules/:id', '删除告警规则', true, ''],
                ['GET', '/api/alerts/logs', '告警记录列表', true, '?status=triggered&page=1&per_page=20'],
                ['POST', '/api/alerts/logs/:id/acknowledge', '确认告警', true, ''],
                ['GET', '/api/alerts/unread-count', '未读告警数量（缓存 30s）', true, ''],
            ],
            '通知' => [
                ['GET', '/api/notifications', '通知列表', true, '?type=alert&is_read=0&page=1&per_page=20'],
                ['GET', '/api/notifications/unread-count', '未读通知数量', true, ''],
                ['POST', '/api/notifications/:id/read', '标记单条已读', true, ''],
                ['POST', '/api/notifications/read-all', '全部已读', true, ''],
            ],
            '自动出价' => [
                ['GET', '/api/bid-rules', '出价规则列表', true, ''],
                ['POST', '/api/bid-rules', '创建出价规则', true, '{"name":"花费超限降预算","metric":"cost","condition":"gt","threshold":5000,"action_type":"adjust_budget","adjust_step":-2000,"cooldown_minutes":60}'],
                ['PUT', '/api/bid-rules/:id', '更新出价规则', true, ''],
                ['DELETE', '/api/bid-rules/:id', '删除出价规则', true, ''],
                ['GET', '/api/bid-rules/logs', '出价调整历史', true, '?rule_id=1'],
            ],
            '定向模板' => [
                ['GET', '/api/targeting-templates', '定向模板列表', true, '?platform=juliang'],
                ['GET', '/api/targeting-templates/:id', '模板详情', true, ''],
                ['POST', '/api/targeting-templates', '创建定向模板', true, '{"name":"核心受众","platform":"","targeting":{"age":{"min":18,"max":45}}}'],
                ['PUT', '/api/targeting-templates/:id', '更新定向模板', true, ''],
                ['DELETE', '/api/targeting-templates/:id', '删除定向模板', true, ''],
            ],
        ];

        $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>Ads Platform API 文档</title>';
        $html .= '<style>body{font-family:-apple-system,sans-serif;max-width:980px;margin:0 auto;padding:20px;background:#f8f9fa}';
        $html .= 'h1{color:#1a1a2e}h2{color:#16213e;border-bottom:2px solid #e0e0e0;padding-bottom:8px;margin-top:28px}';
        $html .= '.endpoint{background:#fff;border-radius:8px;padding:10px 16px;margin:6px 0;box-shadow:0 1px 3px rgba(0,0,0,0.08);display:flex;align-items:center;flex-wrap:wrap;gap:8px}';
        $html .= '.method{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:700;min-width:48px;text-align:center}';
        $html .= '.GET{background:#d4edda;color:#155724}.POST{background:#cce5ff;color:#004085}';
        $html .= '.PUT{background:#fff3cd;color:#856404}.DELETE{background:#f8d7da;color:#721c24}';
        $html .= '.path{font-family:monospace;font-size:14px}.desc{color:#666;font-size:14px;flex:1}';
        $html .= '.extra{font-family:monospace;font-size:12px;color:#888;background:#f5f5f5;padding:2px 6px;border-radius:3px}';
        $html .= '.auth{font-size:11px;color:#28a745;margin-left:4px}';
        $html .= '.toc{margin-bottom:20px}.toc a{color:#409EFF;text-decoration:none;margin-right:16px}';
        $html .= 'footer{text-align:center;color:#aaa;margin-top:40px;font-size:12px}</style></head><body>';
        $html .= '<h1>Ads Platform API</h1>';
        $html .= '<p>Base URL: <code>/api</code> | Header: <code>X-API-Version: v1</code> | Header: <code>X-Client-Platform: web</code></p>';
        $html .= '<p class="toc"><b>跳转：</b>';
        foreach (array_keys($sections) as $s) $html .= '<a href="#' . $s . '">' . $s . '</a>';
        $html .= '</p>';

        foreach ($sections as $group => $routes) {
            $html .= '<h2 id="' . $group . '">' . $group . '</h2>';
            foreach ($routes as $r) {
                $lock = $r[2] ? '<span class="auth">🔒</span>' : '';
                $html .= '<div class="endpoint">';
                $html .= '<span class="method ' . $r[0] . '">' . $r[0] . '</span>';
                $html .= '<span class="path">' . $r[1] . '</span>' . $lock;
                $html .= '<span class="desc">' . $r[3] . '</span>';
                if (!empty($r[4])) $html .= '<span class="extra">' . htmlspecialchars($r[4]) . '</span>';
                $html .= '</div>';
            }
        }
        $html .= '<footer>Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz</footer></body></html>';

        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
