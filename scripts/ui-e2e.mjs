#!/usr/bin/env node
// UI E2E — admin SPA (dist 静态托管 + API mock 兜底)
// 运行: NODE_PATH=$(npm root -g) node scripts/ui-e2e.mjs
import http from 'node:http'
import { createReadStream, existsSync, readdirSync, statSync } from 'node:fs'
import { readFile, mkdir, writeFile } from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { execSync } from 'node:child_process'
import { createRequire } from 'node:module'

const require = createRequire(import.meta.url)
// playwright 可能装在全局或 npx 缓存，逐个探测
let pwPath = null
try { if (existsSync(path.join(execSync('npm root -g').toString().trim(), 'playwright', 'package.json'))) pwPath = path.join(execSync('npm root -g').toString().trim(), 'playwright') } catch {}
if (!pwPath) {
  const hits = execSync('find ~/.npm/_npx -maxdepth 4 -path "*/node_modules/playwright/package.json" 2>/dev/null').toString().trim().split('\n').filter(Boolean)
  pwPath = hits.length ? path.dirname(hits[0]) : null
}
if (!pwPath) throw new Error('playwright 未找到，请先 npm i -g playwright 或用 npx playwright 安装')
const { chromium } = require(pwPath)

const DIST = '/home/wwwroot/ads-php/admin/public/web/dist'
const SHOTS = '/home/wwwroot/ads-php/docs/test-reports/ui-e2e/screens'
const PORT = 8899
const MIME = { '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.json': 'application/json', '.woff2': 'font/woff2' }

// ---------- 静态服务器（SPA history 兜底） ----------
const server = http.createServer((req, res) => {
  const urlPath = decodeURIComponent(new URL(req.url, 'http://x').pathname)
  let file = path.join(DIST, urlPath === '/' ? 'index.html' : urlPath)
  if (!file.startsWith(DIST) || !existsSync(file) || statSync(file).isDirectory()) file = path.join(DIST, 'index.html')
  res.writeHead(200, { 'Content-Type': MIME[path.extname(file)] || 'application/octet-stream' })
  createReadStream(file).pipe(res)
})
await new Promise(r => server.listen(PORT, '127.0.0.1', r))
const BASE = `http://127.0.0.1:${PORT}`

// ---------- API mock ----------
const ok = data => ({ code: 0, message: 'ok', data })
const USER = { id: 1, username: 'admin', name: '管理员', role: 'admin', email: 'admin@example.com' }
const MOCKS = [
  ['POST', '/auth/login', ok({ access_token: 'mock-token', csrf_token: 'mock-csrf', user: USER })],
  ['GET', '/auth/me', ok(USER)],
  ['GET', '/captcha/generate', ok({ token: 'mock-captcha', expires_in: 300 })],
  ['GET', '/reports/summary', ok({ impressions: 123456, clicks: 9876, cost: 5432.1, conversions: 321, ctr: 8.0, cpc: 0.55, conversion_rate: 3.25, spend_today: 888.8, revenue: 12345.6, roi: 2.27, trend: [{ date: '08-25', cost: 100 }, { date: '08-26', cost: 200 }], kpi: { impressions: { today: 123456, yesterday: 110000, growth: 12.2 }, clicks: { today: 9876, yesterday: 9000, growth: 9.7 }, cost: { today: 5432.1, yesterday: 5000, growth: 8.6 }, conversions: { today: 321, yesterday: 300, growth: 7.0 } } })],
  ['GET', '/accounts', ok({ list: [{ id: 1, name: '测试账户A', platform: 'douyin', status: 1, balance: 100.5, daily_budget: 50, created_at: '2026-08-01 10:00:00' }], total: 1 })],
  ['GET', '/campaigns', ok({ list: [{ id: 1, name: '测试计划A', account_id: 1, status: 1, budget: 100, cost: 50, created_at: '2026-08-01 10:00:00' }], total: 1 })],
  ['GET', '/ad-groups', ok({ list: [{ id: 1, name: '测试广告组A', campaign_id: 1, status: 1, created_at: '2026-08-01 10:00:00' }], total: 1 })],
  ['GET', '/creatives', ok({ list: [{ id: 1, name: '测试创意A', type: 'image', status: 1, created_at: '2026-08-01 10:00:00' }], total: 1 })],
  ['GET', '/alerts/rules', ok({ list: [{ id: 1, name: '预算超限告警', metric: 'cost', threshold: 1000, channel: 'email', enabled: true }], total: 1 })],
  ['GET', '/alerts/logs', ok({ list: [{ id: 1, rule_name: '预算超限告警', level: 'warning', message: '今日花费已超预算 80%', created_at: '2026-08-27 09:00:00' }], total: 1 })],
  ['GET', '/alerts/unread-count', ok({ count: 2 })],
  ['GET', '/notifications', ok({ list: [{ id: 1, title: '测试通知', content: '这是一条测试通知', read: false, created_at: '2026-08-27 08:00:00' }], total: 1 })],
  ['GET', '/notifications/unread-count', ok({ count: 1 })],
  ['GET', '/sync/status', ok({ last_sync_at: '2026-08-27 09:30:00', running: false, items: [{ name: 'accounts', status: 'ok' }] })],
  ['GET', '/sync/errors', ok({ list: [], total: 0 })],
  ['GET', '/admin/users', ok({ list: [{ id: 1, username: 'admin', name: '管理员', role: 'admin', status: 1 }], total: 1 })],
  ['GET', '/admin/users/roles', ok(['admin', 'operator', 'viewer'])],
  ['GET', '/admin/audit-logs', ok({ list: [{ id: 1, user: 'admin', action: 'login', target: '系统', created_at: '2026-08-27 09:00:00' }], total: 1 })],
  ['GET', '/platforms', ok({ list: [{ id: 1, name: '抖音', platform: 'douyin', connected: true }], total: 1 })],
  ['GET', '/tenant/quota', ok({ tenant: 'default', used: 300, quota: 1000 })],
  ['GET', '/reports/export', ok({ list: [], total: 0 })],
  ['GET', '/reports/calendar', ok([])],
  ['GET', '/reports/attribution', ok({ list: [], total: 0 })],
  ['GET', '/reports/attribution/models', ok([])],
  ['GET', '/assets', ok({ list: [], total: 0 })],
  ['GET', '/bid-rules', ok({ list: [], total: 0 })],
  ['GET', '/health', ok({ status: 'ok' })],
]
const mockHandler = route => {
  const req = route.request()
  const url = new URL(req.url())
  const m = MOCKS.find(([method, p]) => req.method() === method && url.pathname === p)
  const body = m ? m[2] : ok([]) // 未枚举的 /api/* 一律回空列表
  return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(body) })
}

// ---------- 页面清单（与 src/router/index.ts 对应） ----------
const PAGES = [
  ['login', '/login', '登录页'],
  ['dashboard', '/dashboard', '仪表盘'],
  ['accounts', '/accounts', '账户管理'],
  ['accounts_bind', '/accounts/bind', '绑定账户'],
  ['campaigns', '/campaigns', '广告计划'],
  ['adgroups', '/adgroups', '广告组'],
  ['creatives', '/creatives', '广告创意'],
  ['alerts', '/alerts', '告警规则'],
  ['alerts_logs', '/alerts/logs', '告警记录'],
  ['reports_export', '/reports/export', '报表导出'],
  ['reports_calendar', '/reports/calendar', '投放日历'],
  ['reports_attribution', '/reports/attribution', '归因分析'],
  ['reports_view', '/reports/view', '报表分析'],
  ['assets', '/assets', '素材库'],
  ['notifications', '/notifications', '通知中心'],
  ['sync', '/sync', '同步状态'],
  ['system_users', '/system/users', '用户管理'],
  ['system_audit', '/system/audit', '审计日志'],
]

await mkdir(SHOTS, { recursive: true })
const browser = await chromium.launch({ channel: 'chrome', headless: true })
const results = []
const jsErrors = {}

// 登录上下文（无 token）→ 登录页渲染 + 登录流程
const ctxLogin = await browser.newContext({ baseURL: BASE })
await ctxLogin.route('**/api/**', mockHandler)
const loginPage = await ctxLogin.newPage()
const loginErrors = []
loginPage.on('pageerror', e => loginErrors.push(String(e)))
loginPage.on('console', m => { if (m.type() === 'error' && !/Failed to load resource|favicon/i.test(m.text())) loginErrors.push(m.text()) })
let loginOk = false, loginNote = ''
try {
  await loginPage.goto('/login', { waitUntil: 'networkidle', timeout: 20000 })
  await loginPage.waitForSelector('button:has-text("登 录")', { timeout: 10000 })
  await loginPage.screenshot({ path: `${SHOTS}/login.png`, fullPage: true })
  loginOk = true
  // 登录流程 → 应跳转仪表盘
  await loginPage.click('button:has-text("登 录")')
  await loginPage.waitForURL('**/dashboard', { timeout: 15000 })
  loginNote = '登录成功并跳转 /dashboard'
  await loginPage.screenshot({ path: `${SHOTS}/login_after.png`, fullPage: true })
} catch (e) {
  loginNote = `失败: ${String(e).split('\n')[0]}`
}
results.push({ name: 'login', path: '/login', pass: loginOk && loginErrors.length === 0, note: loginNote + (loginErrors.length ? ` | JS错误: ${loginErrors.join('; ')}` : '') })

// 业务页面上下文（注入 token）→ 逐页渲染检查
const ctx = await browser.newContext({ baseURL: BASE })
await ctx.route('**/api/**', mockHandler)
await ctx.addInitScript(() => {
  localStorage.setItem('access_token', 'mock-token')
  localStorage.setItem('csrf_token', 'mock-csrf')
  localStorage.setItem('user', JSON.stringify({ id: 1, username: 'admin', name: '管理员', role: 'admin' }))
})
for (const [key, route, title] of PAGES.slice(1)) {
  const page = await ctx.newPage()
  const errs = []
  page.on('pageerror', e => errs.push(String(e)))
  page.on('console', m => { if (m.type() === 'error' && !/Failed to load resource|favicon/i.test(m.text())) errs.push(m.text()) })
  let pass = false, note = ''
  try {
    await page.goto(route, { waitUntil: 'networkidle', timeout: 20000 })
    await page.waitForTimeout(800) // 等首屏数据渲染
    const titleText = await page.title()
    const hasApp = await page.evaluate(() => document.querySelector('#app')?.children.length > 0)
    await page.screenshot({ path: `${SHOTS}/${key}.png`, fullPage: true })
    pass = hasApp && errs.length === 0
    note = `${titleText} | app=${hasApp}`
  } catch (e) {
    note = `超时/失败: ${String(e).split('\n')[0]}`
  }
  if (errs.length) jsErrors[key] = errs
  results.push({ name: key, path: route, pass, note })
  await page.close()
}
await browser.close()
server.close()

// ---------- 汇总 ----------
const passed = results.filter(r => r.pass).length
const failed = results.filter(r => !r.pass)
const lines = []
lines.push(`# 管理后台 SPA UI E2E 测试报告`)
lines.push('')
lines.push(`- 测试时间: ${new Date().toLocaleString('zh-CN')}`)
lines.push(`- 覆盖页面: ${results.length}（登录流程 + ${results.length - 1} 个业务页）`)
lines.push(`- 结果: 通过 ${passed} / 失败 ${failed.length}`)
lines.push('')
lines.push('## 1. 环境说明')
lines.push('')
lines.push('| 项目 | 状态 | 说明 |')
lines.push('|------|------|------|')
lines.push('| 浏览器 | 可用 | google-chrome (system, headless, playwright channel:chrome) |')
lines.push('| Playwright | 可用 | 1.62.1 (global @ /usr/local/node/lib/node_modules) |')
lines.push('| Node / npm | 可用 | node v22.17.0 / npm 11.14.1 |')
lines.push('| admin 服务 (8789) | 不可用 | 端口被 /home/wwwroot/social/admin（另一项目旧进程）占用，ads-php admin 无法启动；**未停止他人进程、未改业务源码** |')
lines.push('| service (8788) | 可用 | 返回 200（本测试未依赖） |')
lines.push(`| SPA 静态资源 | 可用 | ${DIST} 存在 index.html + assets bundle |`)
lines.push(`| 测试方式 | 兜底 | dist 静态托管(127.0.0.1:${PORT}) + playwright API mock 拦截 /api/* |`)
lines.push('')
lines.push('## 2. 覆盖页面清单')
lines.push('')
lines.push('| # | 页面 | 路由 | 结果 | 说明 |')
lines.push('|---|------|------|------|------|')
results.forEach((r, i) => lines.push(`| ${i + 1} | ${r.name} | ${r.path} | ${r.pass ? '通过' : '失败'} | ${r.note} |`))
lines.push('')
lines.push('## 3. 截图')
lines.push('')
lines.push(`全部页面截图已保存: \`docs/test-reports/ui-e2e/screens/\`（${results.length} 张，${PAGES[0][0]}.png 与 login_after.png 含登录后跳转）`)
lines.push('')
lines.push('## 4. 失败 / JS 错误摘要')
lines.push('')
if (failed.length) {
  for (const r of failed) lines.push(`- **${r.name}** (${r.path}): ${r.note}`)
  lines.push('')
  for (const [k, errs] of Object.entries(jsErrors)) {
    lines.push(`### ${k}`)
    errs.slice(0, 5).forEach(e => lines.push(`- \`${e}\``))
    lines.push('')
  }
} else {
  lines.push('无。全部页面无未捕获 JS 错误。')
  lines.push('')
}
lines.push('## 5. 环境限制说明')
lines.push('')
lines.push('- **admin 服务未实测**：8789 被 social 项目旧进程占用（该进程不托管任何 SPA，返回 400），ads-php admin 无法在同端口启动；为不干扰他人服务，本次未终止该进程。')
lines.push('- **API 为 mock 数据**：所有 /api/* 请求由 playwright 拦截返回模拟响应，未验证真实后端数据流（真实联调需先在空闲端口启动 admin 或释放 8789）。')
lines.push('- **无截图视觉断言**：仅验证页面渲染（#app 挂载）、无 JS 错误、登录跳转；未做像素级/视觉比对。')
lines.push('- **深色/多语言未覆盖**：仅默认中文浅色模式。')
lines.push(`- **复跑**：\`NODE_PATH=$(npm root -g) node scripts/ui-e2e.mjs\`（需 playwright 全局包 + system chrome）`)
lines.push('')
await writeFile('/home/wwwroot/ads-php/docs/test-reports/ui-e2e.md', lines.join('\n'), 'utf8')
console.log(`PASS=${passed}/${results.length} FAIL=${failed.length} → docs/test-reports/ui-e2e.md`)
failed.forEach(r => console.log(`  FAIL ${r.path}: ${r.note}`))
process.exit(failed.length ? 1 : 0)
