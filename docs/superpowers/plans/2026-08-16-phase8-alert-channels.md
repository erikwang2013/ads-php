# Phase 8: 告警多渠道落地 Implementation Plan

**Goal:** 补齐 Phase 5 遗留缺口——`NotificationService` 的 email/sms 渠道从 echo 存根升级为真实实现（SMTP 邮件 + 通用 Webhook），并支持渠道配置。web 渠道与 Redis pub/sub 已实现，保持不变。

**来源:** Phase 7 团队审计结论（researcher 规划对照：唯一明确"部分完成"项 = Phase 5 告警多渠道，`ads-alert` 缺 `channel/` 目录）

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## 现状（已核实）

| 组件 | 状态 |
|---|---|
| `NotificationService::send()` | `match ($channel)` 分发 web/email/sms；web 真实写入 `erik_notifications`，email/sms 为 echo 存根 |
| `AlertRule.channels` | JSON 字段 + Eloquent cast array，前端已提交 `['web','email','sms']` |
| Admin AlertRuleList.vue | 已有渠道勾选 UI（web 锁定、email/sms 可选） |
| Redis pub/sub | `alert:new` 频道推送已实现 |
| SMTP/邮件配置 | 无（service/config 无 mail 配置） |

## Task 1: 邮件渠道（SMTP）

### Files:
- Create: `service/config/mail.php`（smtp host/port/user/pass/from/from_name/encryption，env 驱动）
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php`（实现 send(AlertLog, AlertRule)）
- Modify: `service/plugin/ads-alert/service/NotificationService.php`（email 分支调用 EmailChannel，移除 echo 存根）
- Modify: `service/composer.json`（如选 PHPMailer 需加依赖；优先考虑不引依赖的 `mail()`/socket 实现以保持轻量，由实现者评估）

### 设计要点
- 收件人：从 AlertRule 配置或租户配置读取（如无，用 `email` 字段或配置默认）
- 主题/正文：复用 sendWeb 的文案模板（"告警触发: {rule.name}" + 指标/当前值/条件/阈值）
- 失败处理：捕获异常记日志，不影响其他渠道与主流程
- 配置缺失时优雅降级（log 提示，不抛异常中断）

## Task 2: Webhook 渠道

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php`（POST JSON 到配置的 URL）
- Modify: `NotificationService::send()` match 增加 `'webhook'` 分支

### 设计要点
- 配置来源：AlertRule 扩展 `webhook_url` 字段（migration）或 channels 配置；为最小改动，优先在 AlertRule 增加 `webhook_url` 列（可空）
- 载荷：`{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`，含告警级别/指标/值/阈值/时间
- 超时与重试：连接超时 5s、总超时 10s，失败记日志（不重试，保持简单）
- 安全：仅允许 http/https，不做内网地址校验（SSRF 风险记为已知限制，或校验非内网——由实现者评估并记录）

## Task 3: 短信渠道（网关占位）

### Files:
- Modify: `NotificationService::sendSms`（保留占位，明确注释接入点；如实现者评估有轻量方案可落地）

### 设计要点
- 短信网关（阿里云/腾讯云）需 AK/SK 与付费，本阶段保留占位实现，注释标明接入步骤
- 前端 UI 的 sms 选项保持可选但后端仅记录日志（明确告知用户未配置网关）

## Task 4: 渠道配置与前端

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue`（如增加 webhook 选项与 URL 输入）
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php`（规则创建/更新接受 webhook_url）
- Modify: `service/plugin/ads-alert/model/AlertRule.php`（fillable/casts 增加 webhook_url）
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql`（ALTER 或说明增量脚本）

### 验收
- [ ] email 渠道：配置 SMTP 后触发告警能收到邮件；未配置时优雅降级
- [ ] webhook 渠道：触发告警时 POST JSON 到配置 URL，载荷字段完整
- [ ] sms 渠道：保持占位，记录日志
- [ ] web 渠道与 Redis pub/sub 回归不受影响
- [ ] Admin 规则表单可配置新渠道字段
- [ ] `php vendor/bin/phpunit --no-coverage` 全过
- [ ] 新增/更新测试：AlertEngine/NotificationService 渠道分发测试
