# Ads Platform — 多平台广告管理系统

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 功能

对接 **29 个广告平台**，统一管理广告投放与数据报表。

### 支持的平台

#### 国内 (16)
巨量引擎、百度营销、淘宝广告、腾讯广告、快手磁力引擎、小红书蒲公英、
微博粉丝通、B站花火、优酷广告、美团广告、知乎广告、360推广、搜狗推广、
友盟、京东京准通、拼多多广告

#### 国际 (13)
Google Ads、YouTube Ads、Meta Ads、TikTok Ads、LinkedIn Ads、
Snapchat Ads、Pinterest Ads、Twitter/X Ads、Amazon Ads、
The Trade Desk、Spotify Ads、Twitch Ads、Netflix Ads

## 技术栈

- **服务端**: webman v2 (PHP 8.2+) + MySQL 8.0 + Redis 7
- **管理后台**: Vue 3 + TypeScript + Element Plus + ECharts 5
- **App**: Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **基础设施**: Docker + Nginx + Elasticsearch

## 快速启动

### Docker (推荐)
```bash
docker-compose up -d
# 初始化数据库
make db-init
```

### 本地开发
```bash
# 服务端
cd service && composer install && php start.php start

# 管理后台
cd admin && npm install && npm run dev
```

## 项目结构
```
ads-php/
├── service/           # PHP 服务端 (webman v2)
│   ├── plugin/
│   │   ├── ads-tenant/      # 多租户
│   │   ├── ads-account/     # 账户 & OAuth
│   │   ├── ads-platform/    # 29个广告平台适配器
│   │   ├── ads-api/         # RESTful API
│   │   ├── ads-task/        # 定时任务调度
│   │   ├── ads-report/      # 报表引擎 & 导出
│   │   └── ads-alert/       # 告警监控
│   ├── config/        # 配置文件
│   └── support/       # 工具类
├── admin/             # Vue3 管理后台
├── flutter/           # Flutter App (PC/Mobile)
├── harmonyos/         # HarmonyOS App
├── docker/            # Docker & Nginx 配置
└── docs/              # 设计文档 & 实施计划
```

## API 端点

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/v1/auth/login | 登录 |
| GET | /api/v1/platforms | 平台列表 |
| GET | /api/v1/accounts | 账户列表 |
| GET | /api/v1/campaigns | 广告计划 |
| GET | /api/v1/reports/summary | 仪表盘 |
| GET | /api/v1/reports/custom | 自定义报表 |
| GET | /api/v1/reports/export | 导出CSV/Excel |
| GET | /api/v1/alerts/rules | 告警规则 |
| GET | /api/v1/alerts/logs | 告警记录 |

## 许可证

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
All rights reserved.
