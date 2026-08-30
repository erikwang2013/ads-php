# 使用说明

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> 安装部署见 README「快速启动」；本文档覆盖安装完成后的完整使用流程。

---

## 1. 首次登录

安装完成后访问管理后台：

- 一键安装 / Docker：`http://localhost`
- 本地开发：`http://localhost:8789`

使用安装向导中设置的管理员用户名和密码登录。登录后进入仪表盘，可查看 8 个 KPI 指标卡片（总花费、展示量、点击量、转化量、点击率、转化率、平均 CPC、平均 CPA）、每日花费趋势折线图、平台花费对比柱状图与 TOP10 广告计划。

修改密码等账户信息：系统管理 → 用户管理。

---

## 2. 平台授权

系统支持 **16 个国内平台 + 13 个国际平台**，全部通过「账户管理 → 绑定账户」进入授权流程。

### OAuth2 平台（占多数）

1. 在绑定账户页面选择目标平台，点击「授权」
2. 浏览器跳转到平台登录页，登录并同意授权
3. 平台回调后系统自动保存 Access Token

授权成功后平台显示在账户列表中。Token 过期由 `TokenRefreshTask` 自动刷新（每小时 55 分），无需人工干预。

### API Key 平台

360 推广、搜狗推广、友盟等平台使用 API Key 认证：在绑定账户页面手动填入 API Key（及签名所需参数），保存后即可同步。

> 国内 16 平台：巨量引擎、百度营销、淘宝/阿里妈妈、腾讯广告、快手磁力引擎、小红书蒲公英、微博粉丝通、B站花火、优酷广告、美团广告、知乎广告、360推广、搜狗推广、友盟、京东京准通、拼多多广告
>
> 国际 13 平台：Google Ads、YouTube Ads、Meta Ads、TikTok Ads、LinkedIn Ads、Snapchat Ads、Pinterest Ads、Twitter/X Ads、Amazon Ads、The Trade Desk、Spotify Ads、Twitch Ads、Netflix Ads

---

## 3. 账户绑定与素材库上传

### 账户管理

平台授权后，账户出现在「账户管理」列表。每个账户可独立控制是否参与同步（`sync_enabled`）。投放层级为 Campaign（广告计划）→ AdGroup（广告组）→ Creative（广告创意）三级结构。

### 素材库

「素材库」支持上传图片/视频素材并画廊式浏览，供广告创意引用。上传的素材可选用 CDN 存储（见下）。

### CDN 存储服务商配置

系统内置存储抽象，支持多种驱动，可同时配置多家服务商：

| 驱动 | 说明 |
|------|------|
| 本地存储 | 默认驱动，存放到服务器磁盘 |
| 阿里云 OSS | AlibabaOssStorage |
| 腾讯云 COS | TencentCosStorage |
| S3 兼容 | S3CompatibleStorage（兼容 AWS S3、七牛云、MinIO 等） |

在「CDN 服务商」页面添加服务商并填写对应密钥/区域等参数后即可启用。

### 预签名直传与缓存刷新

- **预签名直传**：服务端为每个上传签发带时效的预签名 URL（OSS/S3 的 PUT），浏览器或移动端直接向对象存储上传文件，不经过应用服务器，减轻带宽与负载
- **缓存刷新**：素材更新或删除后，可触发 CDN 缓存刷新（purge），保证客户端获取最新内容

---

## 4. 数据同步

同步由 6 个定时任务驱动（webman crontab 插件进程内调度，无需外部 crontab）：

| 任务 | 频率 | 职责 |
|------|------|------|
| RetrySyncTask | 每 3 分钟 | 重试上次失败的同步 |
| AlertCheckTask | 每 5 分钟 | 告警规则求值 |
| DataSyncTask | 每 10 分钟 | 同步 Campaign/AdGroup/Creative 与报表（过去 2 天、9 个指标） |
| BidCheckTask | 每 10 分钟 | 自动出价规则检查 |
| BudgetCheckTask | 每 15 分钟 | 预算预警检查 |
| TokenRefreshTask | 每小时 55 分 | 刷新过期平台 Token |

任务配置位于 `service/plugin/ads-task/config/cron.php`，可修改频率。同步状态可在「数据同步」页面查看，账户级启停开关在「账户管理」。

---

## 5. 报表分析

### 仪表盘

8 个 KPI 指标卡片 + 日趋势折线图 + 平台对比柱状图 + TOP10 广告计划，支持时间范围筛选与一键导出 PDF/Excel。

### 自定义报表

- **维度**：date、platform、campaign
- **指标**：cost、impressions、clicks、conversions、ctr、cvr、cpc、cpm、roi
- 支持按维度组合查询与排序

### 归因分析

内置跨平台归因引擎，支持 **5 种归因模型**：first_touch（首次触点）、last_touch（末次触点）、linear（线性均分）、time_decay（时间衰减）、position_based（位置加权），回溯窗口 30 天。在「归因分析」页面选择模型与日期范围查看各渠道贡献。

### 投放日历

「投放日历」以日历视图展示各计划的投放安排，直观查看每日投放节奏。

### 导出

报表支持三种导出格式：

- **CSV**（UTF-8 BOM，Excel 直接打开不乱码）
- **Excel**（HTML .xls）
- **PDF**（HTML 打印排版）

---

## 6. 告警与通知

### 告警规则

「告警规则」页面创建规则：选择监控对象（预算/花费/展示/点击等指标）、阈值与比较方式、生效范围，并选择通知渠道。规则启用后由 `AlertCheckTask` 每 5 分钟求值，命中即触发。

### 通知渠道

| 渠道 | 说明 |
|------|------|
| Web | 站内通知，在「通知中心」查看 |
| Email | 邮件发送（SMTP，`mail()` 兜底），在告警规则中配置收件地址 |
| SMS | 短信发送 |
| Webhook | POST JSON 到配置的回调 URL，可对接企业微信/钉钉/飞书等 |

告警历史在「告警记录」页面查看。

---

## 7. 移动端

### Flutter App（12 个页面：登录/仪表盘/账户/计划/广告组/创意/报表/出价/告警/通知等）

```bash
cd apps/flutter
flutter run -d chrome     # Web PC
flutter run -d android    # Android 手机
```

### HarmonyOS App

使用 DevEco Studio 打开 `apps/harmonyos` 目录运行。

---

## 8. 多租户

系统内置多租户插件（ads-tenant）：

- **租户识别**：`TenantIdentify` 中间件按请求识别当前租户
- **数据隔离**：支持两种模式 — 共享数据库按 `tenant_id` 隔离，或按租户使用独立数据库（`db_type`）
- **配额管理**：`QuotaService` 校验租户配额（账户数、素材数等），超限请求被拒绝

---

## 相关文档

- [功能设计文档](features.md) — 21 模块/业务流程
- [API 接口文档](api.md) — 全部接口定义
- [架构设计文档](architecture.md) — 部署/安全/数据模型
