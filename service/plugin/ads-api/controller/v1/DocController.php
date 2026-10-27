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
                ['GET', '/api/v1/captcha/generate', '生成滑块验证码', false, ''],
                ['POST', '/api/v1/captcha/verify', '验证滑块偏移量', false, '{"captcha_token":"...","captcha_offset":120}'],
            ],
            '认证' => [
                ['POST', '/api/v1/auth/login', '登录获取 JWT Token', false, '{"username":"admin","password":"******"}'],
                ['GET', '/api/v1/auth/me', '当前用户信息', true, ''],
                ['POST', '/api/v1/auth/refresh', '刷新 JWT Token（旧 Token 自动黑名单）', true, ''],
            ],
            '平台 & 账户' => [
                ['GET', '/api/v1/platforms', '29 个适配平台列表（缓存 1h）', false, ''],
                ['GET', '/api/v1/platforms/:code/oauth-url', '获取平台 OAuth 授权 URL', true, '?redirect_uri=...'],
                ['POST', '/api/v1/platforms/:code/callback', 'OAuth 回调处理', true, '{"state":"...","code":"..."}'],
                ['GET', '/api/v1/accounts', '已绑定账户列表（缓存 5min）', true, '?platform=juliang&page=1&per_page=20'],
                ['GET', '/api/v1/accounts/:id', '账户详情（缓存 5min）', true, ''],
                ['DELETE', '/api/v1/accounts/:id', '解绑账户', true, ''],
                ['POST', '/api/v1/accounts/:id/sync', '手动触发数据同步', true, ''],
            ],
            '广告计划' => [
                ['GET', '/api/v1/campaigns', '计划列表（筛选/排序/分页）', true, '?platform=juliang&status=enabled&sort=cost&page=1&per_page=20'],
            ],
            '仪表盘' => [
                ['GET', '/api/v1/reports/summary', '仪表盘汇总（缓存 5min）', true, '?date_start=2026-05-01&date_end=2026-05-21'],
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
        $html .= 'footer{text-align:center;color:#aaa;margin-top:40px;font-size:12px}';
        $html .= '.pet{width:52px;height:52px;vertical-align:middle;margin-right:12px}</style></head><body>';
        $html .= '<h1><img class="pet" src="/img/pet.svg" alt="阿章" title="阿章 — 八爪值守 29 个平台">Ads Platform API</h1>';
        $html .= '<p>Base URL: <code>/api/v1</code> | Header: <code>X-Client-Platform: web</code> | 版本号固定于路径（v1 为当前版本）</p>';
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
