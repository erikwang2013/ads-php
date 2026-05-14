# 多平台广告管理系统设计

## 概述

对接巨量引擎、京东广告、淘宝广告、拼多多广告、百度广告、谷歌广告、YouTube广告、TikTok广告等国内外广告厂商的统一广告管理平台。

- **服务端**：webman v2（PHP 8.2+）
- **管理后台**：webman-admin v2（Vue3 + TypeScript + Element Plus）
- **App**：Flutter（iOS / Android / Web/PC 响应式）+ HarmonyOS（ArkTS + ArkUI）

业务场景覆盖自用投放、SaaS 多租户、代运营三种模式。

---

## 总体架构

```
┌─────────────────────────────────────────────────────┐
│                    Client Layer                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ Flutter  │  │ HarmonyOS│  │ webman-admin v2  │   │
│  │   App    │  │   App    │  │   (Vue3+TS)      │   │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘   │
└───────┼──────────────┼────────────────┼─────────────┘
        │              │                │
        └──────────────┼────────────────┘
                       │ HTTP/WebSocket
               ┌───────┴────────┐
               │   API Gateway   │
               │  (webman v2)   │
               └───────┬────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   ┌────┴────┐   ┌────┴────┐   ┌────┴────┐
   │ Tenant  │   │  Auth   │   │  Rate   │
   │Resolver │   │ Service │   │ Limiter │
   └────┬────┘   └────┬────┘   └────┬────┘
        │              │              │
   ┌────┴──────────────┴──────────────┴────┐
   │            Service Layer               │
   │  ┌──────────┐ ┌──────┐ ┌──────────┐  │
   │  │ Campaign │ │Report│ │Account   │  │
   │  │ Manager  │ │Engine│ │Manager   │  │
   │  └────┬─────┘ └──┬───┘ └────┬─────┘  │
   │       └──────────┼──────────┘         │
   │          ┌───────┴────────┐           │
   │          │ Platform       │           │
   │          │ Adapter Layer  │           │
   │          └───────┬────────┘           │
   └──────────────────┼────────────────────┘
                      │
   ┌──────────────────┼────────────────────┐
   │       Platform Adapters               │
   │  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐     │
   │  │巨量 │ │百度 │ │淘宝 │ │Google│ ... │
   │  │引擎 │ │广告 │ │广告 │ │ Ads │     │
   │  └──┬──┘ └──┬──┘ └──┬──┘ └──┬──┘     │
   └─────┼───────┼───────┼───────┼─────────┘
         │       │       │       │
    外部广告平台 APIs
```

---

## 一、服务端模块拆解

webman v2 使用插件机制，`service/plugin/` 下划分：

```
service/
├── plugin/
│   ├── ads-tenant/          # 多租户管理
│   │   ├── config/plugin.php, database.php
│   │   ├── model/Tenant.php, TenantDatabase.php
│   │   └── middleware/TenantIdentify.php
│   │
│   ├── ads-account/         # 广告账户 & 授权
│   │   ├── model/PlatformAccount.php, AuthToken.php
│   │   └── service/OAuthService.php
│   │
│   ├── ads-campaign/        # 投放管理（统一模型）
│   │   ├── model/Campaign.php, AdGroup.php, Creative.php
│   │   └── service/CampaignService.php, SyncService.php
│   │
│   ├── ads-report/          # 报表引擎
│   │   ├── model/ReportMetric.php, ReportExtra.php
│   │   └── service/ReportAggregator.php, ReportCache.php
│   │
│   ├── ads-platform/        # 平台适配器核心
│   │   ├── src/PlatformAdapter.php, AdapterRegistry.php, FieldMapping.php
│   │   └── adapter/Juliang.php, Baidu.php, Taobao.php, Google.php, Tiktok.php ...
│   │
│   ├── ads-task/            # 异步任务 & 调度
│   │   ├── task/DataSyncTask.php, ReportBuildTask.php, TokenRefreshTask.php
│   │   └── config/cron.php
│   │
│   └── ads-api/             # RESTful API 路由
│       ├── controller/CampaignController.php, ReportController.php ...
│       └── config/route.php
```

---

## 二、平台适配器（核心）

### 接口定义

```php
interface PlatformAdapter
{
    public function code(): string;
    public function name(): string;
    public function capabilities(): array;

    // 授权
    public function buildAuthUrl(string $redirectUri): string;
    public function exchangeToken(string $code): array;
    public function refreshToken(string $refreshToken): array;

    // 数据同步（流式返回统一对象）
    public function fetchCampaigns(string $accountId): Generator;
    public function fetchAdGroups(string $accountId, string $campaignId): Generator;
    public function fetchCreatives(string $accountId, string $adGroupId): Generator;
    public function fetchReports(string $accountId, ReportRequest $req): Generator;

    // 投放操作
    public function createCampaign(string $accountId, CampaignData $data): string;
    public function updateCampaign(string $accountId, string $platformId, CampaignData $data): void;
    public function toggleCampaign(string $accountId, string $platformId, bool $enabled): void;
}
```

### 字段映射

每个适配器维护自己的字段映射表，将平台原始字段转为统一字段，不在映射表中的字段落入 `extra` JSON 扩展字段。

```php
// 巨量引擎适配器
protected array $fieldMap = [
    'campaign_id'   => 'platform_campaign_id',
    'campaign_name' => 'name',
    'budget'        => 'daily_budget',       // 分 → 分
    'stat_cost'     => 'cost',
    'show_cnt'      => 'impressions',
    'click_cnt'     => 'clicks',
    'convert_cnt'   => 'conversions',
];

// Google Ads 适配器
protected array $fieldMap = [
    'campaign.id'               => 'platform_campaign_id',
    'campaign.name'             => 'name',
    'campaign_budget.amount_micros' => 'daily_budget',  // 微元 → 分
    'metrics.cost_micros'       => 'cost',
    'metrics.impressions'       => 'impressions',
    'metrics.clicks'            => 'clicks',
    'metrics.conversions'       => 'conversions',
];
```

---

## 三、数据库设计（核心表）

### 数据模型策略

混合模式：核心字段统一（cost, impressions, clicks, conversions），平台特有字段用 JSON `extra` 字段存储。

### 租户隔离策略

混合模式：中小租户共享数据库（tenant_id 隔离），大客户/高敏感客户独立数据库，通过 `tenants.db_type` 和 `tenants.db_config` 动态路由。

### 核心表

```sql
-- 租户
CREATE TABLE tenants (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    db_type ENUM('shared','dedicated') DEFAULT 'shared',
    db_config JSON NULL,
    plan ENUM('free','pro','enterprise') DEFAULT 'free',
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 平台账户
CREATE TABLE platform_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    platform VARCHAR(32) NOT NULL,
    account_id_on_platform VARCHAR(128) NOT NULL,
    account_name VARCHAR(255),
    access_token TEXT,
    refresh_token VARCHAR(512),
    token_expires_at DATETIME,
    status TINYINT DEFAULT 1,
    sync_enabled TINYINT DEFAULT 1,
    last_sync_at DATETIME,
    UNIQUE KEY uk_platform_account (tenant_id, platform, account_id_on_platform)
);

-- 统一广告计划
CREATE TABLE campaigns (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    platform_account_id BIGINT NOT NULL,
    platform VARCHAR(32) NOT NULL,
    platform_campaign_id VARCHAR(128) NOT NULL,
    name VARCHAR(255) NOT NULL,
    daily_budget BIGINT DEFAULT 0,       -- 单位：分
    total_budget BIGINT DEFAULT 0,
    status VARCHAR(32),
    start_date DATE,
    end_date DATE,
    extra JSON,
    synced_at DATETIME,
    UNIQUE KEY uk_platform_campaign (platform_account_id, platform_campaign_id)
);

-- 统一广告组
CREATE TABLE ad_groups (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    campaign_id BIGINT NOT NULL,
    platform_adgroup_id VARCHAR(128) NOT NULL,
    name VARCHAR(255),
    status VARCHAR(32),
    bid_amount BIGINT DEFAULT 0,          -- 单位：分
    bid_type VARCHAR(32),
    targeting JSON,
    extra JSON,
    UNIQUE KEY uk_platform_adgroup (campaign_id, platform_adgroup_id)
);

-- 统一创意
CREATE TABLE creatives (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ad_group_id BIGINT NOT NULL,
    platform_creative_id VARCHAR(128) NOT NULL,
    title VARCHAR(500),
    description TEXT,
    media_type VARCHAR(32),
    media_urls JSON,
    landing_url VARCHAR(2048),
    extra JSON,
    UNIQUE KEY uk_platform_creative (ad_group_id, platform_creative_id)
);

-- 报表核心指标
CREATE TABLE report_metrics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    platform_account_id BIGINT NOT NULL,
    platform VARCHAR(32) NOT NULL,
    campaign_id BIGINT,
    ad_group_id BIGINT,
    creative_id BIGINT,
    date DATE NOT NULL,
    granularity VARCHAR(16) DEFAULT 'daily',

    cost BIGINT DEFAULT 0,                -- 消耗，单位：分
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    ctr DECIMAL(8,4) DEFAULT 0,
    cpm DECIMAL(10,2) DEFAULT 0,          -- 分
    cpc DECIMAL(10,2) DEFAULT 0,          -- 分
    cvr DECIMAL(8,4) DEFAULT 0,

    UNIQUE KEY uk_report (tenant_id, platform, platform_account_id, campaign_id, ad_group_id, creative_id, date, granularity),
    INDEX idx_date (date),
    INDEX idx_campaign_date (campaign_id, date)
);

-- 报表扩展数据
CREATE TABLE report_extras (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_metric_id BIGINT NOT NULL,
    platform VARCHAR(32) NOT NULL,
    extra JSON,
    FOREIGN KEY (report_metric_id) REFERENCES report_metrics(id)
);
```

---

## 四、Web 管理后台（webman-admin v2）

技术栈：Vue3 + TypeScript + Element Plus + ECharts 5 + Pinia

### 页面结构

```
admin/src/views/
├── dashboard/
│   ├── Index.vue              # 总览：指标卡片 + 趋势图 + 平台占比 + TOP10
│   └── PlatformCompare.vue    # 跨平台对比
├── account/
│   ├── AccountList.vue        # 平台账户列表
│   ├── AccountBind.vue        # OAuth 绑定引导
│   └── TokenManage.vue        # Token 状态/刷新
├── campaign/
│   ├── CampaignList.vue       # 跨平台计划列表（表格+筛选+批量操作）
│   ├── CampaignCreate.vue     # 创建（动态表单schema）
│   ├── CampaignEdit.vue       # 编辑
│   └── CampaignBatch.vue      # 批量启停/改价/改预算
├── creative/
│   ├── CreativeList.vue
│   └── CreativeAnalyze.vue    # 创意效果分析
├── report/
│   ├── ReportDaily.vue        # 日报
│   ├── ReportHourly.vue       # 小时报
│   ├── ReportCustom.vue       # 自定义报表（维度/指标自选）
│   └── ReportExport.vue       # 导出 Excel/PDF
├── tenant/
│   ├── TenantList.vue         # 租户管理
│   ├── TenantConfig.vue       # 套餐/配额/数据库路由
│   └── TenantBill.vue         # 计费账单
├── task/
│   ├── SyncTask.vue           # 同步任务状态
│   └── TaskLog.vue            # 任务日志
└── system/
    ├── UserManage.vue
    ├── RolePermission.vue
    └── AuditLog.vue
```

### 关键页面能力

| 页面 | 能力 |
|------|------|
| 仪表盘 | 顶部指标卡片行，ECharts 趋势图（按平台分色），平台占比饼图，TOP10 排名 |
| 计划列表 | 表格列含平台标签/状态/日预算/今日花费/CTR/CVR，按平台和状态筛选，批量操作 |
| 创建计划 | 选平台→选账户→动态加载该平台表单（后端适配器返回表单 schema） |
| 自定义报表 | 拖拽选择维度，勾选指标，动态图表+表格，保存查询模板 |

---

## 五、Flutter App（Mobile + Web/PC 响应式）

### 响应式断点

| 断点 | 宽度 | 布局 | 目标 |
|------|------|------|------|
| Mobile | < 600px | 单列，底部导航栏，卡片列表 | 手机 |
| Tablet | 600-1200px | 双列网格，侧边抽屉导航 | 平板 |
| Desktop | > 1200px | 多列网格，固定侧边导航，数据表格 | PC |

### 页面结构

```
lib/
├── features/
│   ├── dashboard/
│   │   ├── dashboard_page.dart        # 自适应布局
│   │   └── widgets/summary_card.dart, trend_chart.dart, platform_ranking.dart
│   ├── campaign/
│   │   ├── campaign_list_page.dart     # Mobile:卡片 / PC:表格
│   │   ├── campaign_detail_page.dart   # Mobile:纵向 / PC:左右分栏
│   │   ├── campaign_create_page.dart   # PC完整表单 / Mobile分步向导
│   │   └── widgets/campaign_card.dart, campaign_table.dart, campaign_form.dart
│   ├── report/
│   │   ├── report_page.dart            # Mobile简要 / PC完整报表
│   │   ├── custom_report_page.dart     # PC拖拽维度/指标
│   │   └── widgets/report_chart.dart, report_table.dart
│   ├── account/
│   │   ├── account_page.dart
│   │   └── bind_account_page.dart      # WebView OAuth
│   ├── alert/
│   │   ├── alert_list_page.dart
│   │   └── alert_rule_page.dart
│   └── settings/
├── shared/
│   ├── models/
│   ├── api/Dio封装 + endpoints
│   ├── widgets/responsive_layout.dart, data_table_view.dart, side_nav.dart
│   └── utils/
└── core/theme.dart, router.dart(GoRouter), di.dart
```

### PC 端 / Mobile 端差异

| 页面 | Mobile | PC |
|------|--------|----|
| 创建计划 | 分步向导（3步） | 完整表单，一屏完成 |
| 计划列表 | 卡片 + 下拉筛选 | DataGrid 表格 + 顶部筛选 + 批量操作栏 |
| 自定义报表 | 不支持 | 拖拽维度/指标 + 图表 + 导出 |
| 计划详情 | 纵向滚动 | 左右分栏：数据表格 \| 趋势图 |
| 导航 | 底部 TabBar | 左侧固定 SideNav |

### Flutter Web/PC 与管理后台分工

- **webman-admin**：重型管理（深度报表/系统配置/租户管理）
- **Flutter Web/PC**：轻量运营面板（实时盯盘/告警处理/轻量投放，无需 VPN 随时访问）

---

## 六、HarmonyOS App

技术栈：ArkTS + ArkUI，功能与 Flutter App 一致。

```
entry/src/main/ets/
├── pages/LoginPage, DashboardPage, CampaignListPage, CampaignDetailPage, ReportPage,
│         AccountPage, AlertPage, SettingsPage
├── widgets/MetricCard, TrendChart, PlatformBadge, CampaignCard, EmptyState
├── model/Campaign, ReportMetric, PlatformAccount
├── api/ApiClient + Endpoints
├── store/AppState, UserStore
├── service/PushService, BackgroundTaskService
└── utils/CurrencyUtil, DateUtil, NumUtil
```

鸿蒙特有特性：桌面服务卡片（2×2 显示今日花费/ROI）、推送直达、平板报表大屏流转。

---

## 七、API 设计

前缀 `/api/v1`，RESTful 风格，统一响应格式：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [...],
    "pagination": { "page": 1, "per_page": 20, "total": 156, "total_pages": 8 },
    "summary": {
      "total_cost": 1258000,
      "total_impressions": 238000,
      "total_clicks": 15200,
      "avg_ctr": 6.39,
      "avg_cvr": 2.81,
      "avg_roi": 3.2
    }
  }
}
```

### 主要端点

```
POST   /api/v1/auth/login|refresh
GET    /api/v1/auth/me

GET    /api/v1/accounts                        # 账户列表
POST   /api/v1/accounts                        # 绑定账户
DELETE /api/v1/accounts/:id                    # 解绑
POST   /api/v1/accounts/:id/sync               # 手动同步
GET    /api/v1/platforms                       # 平台列表
GET    /api/v1/platforms/:code/oauth-url       # OAuth URL
POST   /api/v1/platforms/:code/callback        # OAuth 回调

GET    /api/v1/campaigns                       # 计划列表
POST   /api/v1/campaigns                       # 创建
GET    /api/v1/campaigns/:id                   # 详情
PUT    /api/v1/campaigns/:id                   # 更新
POST   /api/v1/campaigns/:id/toggle            # 启停
POST   /api/v1/campaigns/batch-toggle          # 批量启停
POST   /api/v1/campaigns/batch-bid             # 批量改出价

GET    /api/v1/reports/daily|hourly|custom     # 报表
GET    /api/v1/reports/summary                 # 仪表盘汇总

GET    /api/v1/tenants                         # 租户列表
POST   /api/v1/tenants                         # 创建租户
PUT    /api/v1/tenants/:id                     # 更新
GET    /api/v1/tenants/:id/billing             # 账单

GET    /api/v1/alerts                          # 告警列表
POST   /api/v1/alerts                          # 创建规则
PUT    /api/v1/alerts/:id                      # 更新
DELETE /api/v1/alerts/:id                      # 删除
```

---

## 八、数据同步 & 任务调度

使用 `webman/crontab`，Redis 队列做异步任务。

| 任务 | 频率 | 说明 |
|------|------|------|
| TokenRefreshTask | 每小时 | 扫描即将过期的 Token，自动刷新 |
| DataSyncTask | 每10分钟 | 拉取各平台近2小时投放数据 |
| ReportBuildTask | 每天凌晨 | 预计算昨日日报、月报汇总 |
| DailySummaryTask | 每30分钟 | 更新仪表盘缓存 |
| AlertCheckTask | 每5分钟 | 检查告警规则，触发推送 |
| CleanupTask | 每天凌晨 | 清理过期日志、临时文件 |

同步策略：适配器内实现统一速率控制，Generator 流式处理，增量同步优先，失败自动重试 3 次。

---

## 九、部署架构

```
                    ┌──────────────┐
                    │   Nginx LB   │
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
         ┌────┴────┐  ┌────┴────┐  ┌────┴────┐
         │ webman  │  │ webman  │  │ webman  │
         │ 实例 1  │  │ 实例 2  │  │ 实例 3  │
         └────┬────┘  └────┬────┘  └────┬────┘
              │            │            │
              └────────────┼────────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
         ┌────┴────┐  ┌────┴────┐  ┌────┴────┐
         │  MySQL  │  │  Redis  │  │  NFS/   │
         │ 主从    │  │  缓存   │  │  OSS    │
         └─────────┘  └─────────┘  └─────────┘
```

技术选型：PHP 8.2+ / MySQL 8.0 / Redis 7 / webman/redis-queue / Vue 3 + TS + Element Plus / ECharts 5 / Flutter 3.x / ArkTS

---

## 十、分阶段实施计划

```
Phase 1 — 基础骨架（2-3周）
├── webman v2 项目初始化
├── webman-admin v2 项目初始化
├── 多租户插件（ads-tenant）
├── 认证 & 权限（RBAC）
├── 账户管理 + OAuth 流程框架
└── 巨量引擎适配器（第一个平台，跑通全链路）

Phase 2 — 投放 + 报表（2-3周）
├── 统一投放管理（ads-campaign）
├── 报表引擎（ads-report）
├── 仪表盘（Admin 首页）
├── 百度广告适配器
└── 淘宝广告适配器

Phase 3 — 扩展平台 + 增强（2-3周）
├── Google Ads 适配器
├── TikTok Ads 适配器
├── 自定义报表
├── 任务调度与同步监控
├── 告警系统
└── 计费基础

Phase 4 — App + 鸿蒙 + 剩余平台（3-4周）
├── Flutter App（仪表盘/计划列表/告警）
├── HarmonyOS App
├── 拼多多/京东/YouTube 适配器
├── 批量操作增强
└── 性能优化 & 压测

Phase 5 — 稳定与运营（持续）
├── 数据质量监控
├── API 文档 & SDK
├── 运维工具
└── 持续优化
```
