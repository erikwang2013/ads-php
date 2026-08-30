# Usage Guide

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> For installation and deployment see the "Quick Start" section of the README; this document covers the complete workflow after installation.

---

## 1. First Login

After installation, open the admin console:

- One-click install / Docker: `http://localhost`
- Local development: `http://localhost:8789`

Log in with the administrator username and password set in the installer. After login you land on the dashboard with 8 KPI metric cards (total cost, impressions, clicks, conversions, CTR, CVR, average CPC, average CPA), a daily cost trend line chart, a platform cost comparison bar chart, and the TOP 10 campaigns.

To change your password or account info: System Management → User Management.

---

## 2. Platform Authorization

The system supports **16 domestic platforms + 13 international platforms**, all authorized through "Account Management → Bind Account".

### OAuth2 Platforms (the majority)

1. Select the target platform on the Bind Account page and click "Authorize"
2. The browser redirects to the platform login page; sign in and approve access
3. After the callback, the system automatically stores the Access Token

Authorized platforms appear in the account list. Expired tokens are automatically refreshed by `TokenRefreshTask` (at minute 55 of every hour) — no manual intervention needed.

### API Key Platforms

Platforms such as Qihoo360, Sogou and Umeng use API Key authentication: fill in the API Key (and any signature parameters) manually on the Bind Account page, save, and synchronization starts.

> 16 domestic platforms: Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou Magnetic Engine, Xiaohongshu Floral, Weibo Fans Tong, Bilibili Huahuo, Youku Ads, Meituan Ads, Zhihu Ads, Qihoo360 Promotion, Sogou Promotion, Umeng, JD Jingzhun Tong, Pinduoduo Ads
>
> 13 international platforms: Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. Account Binding and Creative Library Upload

### Account Management

After platform authorization, accounts appear in the "Account Management" list. Each account can independently control whether it participates in sync (`sync_enabled`). The ad hierarchy is a three-level structure: Campaign → AdGroup → Creative.

### Creative Library

The "Creative Library" supports uploading image/video assets with gallery-style browsing, for use by ad creatives. Uploaded assets can optionally use CDN storage (below).

### CDN Storage Provider Configuration

The system has a built-in storage abstraction supporting multiple drivers; multiple providers can be configured at the same time:

| Driver | Description |
|--------|-------------|
| Local Storage | Default driver, stores on the server disk |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| S3 Compatible | S3CompatibleStorage (compatible with AWS S3, Qiniu Cloud, MinIO, etc.) |

Add a provider on the "CDN Provider" page and fill in the corresponding keys/region parameters to enable it.

### Presigned Upload and Cache Purge

- **Presigned upload**: the server issues a time-limited presigned URL (OSS/S3 PUT) for each upload; browsers or mobile clients upload files directly to the object storage, bypassing the application server and reducing bandwidth and load
- **Cache purge**: after an asset is updated or deleted, a CDN cache purge can be triggered so clients always get the latest content

---

## 4. Data Sync

Synchronization is driven by 6 scheduled tasks (scheduled in-process by the webman crontab plugin — no external crontab required):

| Task | Frequency | Responsibility |
|------|-----------|----------------|
| RetrySyncTask | Every 3 minutes | Retry the last failed sync |
| AlertCheckTask | Every 5 minutes | Evaluate alert rules |
| DataSyncTask | Every 10 minutes | Sync Campaign/AdGroup/Creative and reports (last 2 days, 9 metrics) |
| BidCheckTask | Every 10 minutes | Check automatic bid rules |
| BudgetCheckTask | Every 15 minutes | Budget alert checks |
| TokenRefreshTask | Minute 55 of every hour | Refresh expired platform tokens |

Task configuration lives in `service/plugin/ads-task/config/cron.php`; frequencies can be modified there. Sync status is visible on the "Data Sync" page; per-account on/off switches are in "Account Management".

---

## 5. Report Analytics

### Dashboard

8 KPI metric cards + daily trend line chart + platform comparison bar chart + TOP 10 campaigns, with a date range filter and one-click PDF/Excel export.

### Custom Reports

- **Dimensions**: date, platform, campaign
- **Metrics**: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Supports combined dimension queries and sorting

### Attribution Analysis

A built-in cross-platform attribution engine supports **5 attribution models**: first_touch, last_touch, linear, time_decay, position_based, with a 30-day lookback window. Pick a model and date range on the "Attribution Analysis" page to see each channel's contribution.

### Campaign Calendar

The "Campaign Calendar" shows each campaign's delivery schedule in a calendar view for a quick look at the daily delivery rhythm.

### Export

Reports support three export formats:

- **CSV** (UTF-8 BOM, opens directly in Excel without garbled text)
- **Excel** (HTML .xls)
- **PDF** (HTML print layout)

---

## 6. Alerts and Notifications

### Alert Rules

Create rules on the "Alert Rules" page: choose the monitored object (budget/cost/impressions/clicks etc.), the threshold and comparison, the effective scope, and the notification channels. Enabled rules are evaluated by `AlertCheckTask` every 5 minutes and trigger when matched.

### Notification Channels

| Channel | Description |
|---------|-------------|
| Web | In-app notifications, viewable in the "Notification Center" |
| Email | Sent by email (SMTP, with `mail()` fallback); configure recipient addresses in the alert rule |
| SMS | Sent by SMS |
| Webhook | POSTs JSON to a configured callback URL; can integrate with WeCom/DingTalk/Feishu etc. |

Alert history is viewable on the "Alert Logs" page.

---

## 7. Mobile Apps

### Flutter App (12 pages: Login/Dashboard/Accounts/Campaigns/Ad Groups/Creatives/Reports/Bids/Alerts/Notifications etc.)

```bash
cd apps/flutter
flutter run -d chrome     # Web PC
flutter run -d android    # Android phone
```

### HarmonyOS App

Open the `apps/harmonyos` directory with DevEco Studio and run.

---

## 8. Multi-Tenancy

The system has a built-in multi-tenant plugin (ads-tenant):

- **Tenant identification**: the `TenantIdentify` middleware identifies the current tenant per request
- **Data isolation**: two modes — shared database isolated by `tenant_id`, or a separate database per tenant (`db_type`)
- **Quota management**: `QuotaService` validates tenant quotas (account count, asset count, etc.); over-quota requests are rejected

---

## Related Documents

- [Features](features.en.md) — 21 modules/business flows
- [API Reference](api.en.md) — all interface definitions
- [Architecture](architecture.en.md) — deployment/security/data model
