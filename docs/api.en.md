# API Reference

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **hg/apidoc live docs**: visit `http://127.0.0.1:8788/apidoc` after starting the service (Service + Admin dual-app switcher)  
> Config file: `service/config/plugin/hg/apidoc/app.php`

---

## General Conventions

### Base URL

```
http://your-domain.com/api/v1
```

> The API version is fixed in the URL path (currently `v1`) and is not passed via a Header; future major versions such as `/api/v2` follow the same rule.

### Required Headers

| Header | Value | Description |
|--------|-------|-------------|
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Client source (required) |
| `Authorization` | `Bearer <token>` | JWT auth token (required except for login/platform list/health check) |

### Anti-Replay Headers (non-browser)

| Header | Description |
|--------|-------------|
| `X-Nonce` | Random string (unique per request) |
| `X-Timestamp` | Unix timestamp in seconds (±5 minute window) |

### Optional Headers

| Header | Description |
|--------|-------------|
| `X-Tenant-Id` | Tenant ID (multi-tenant mode) |
| `X-Encrypted` | `1` = request body must be decrypted, response body will be encrypted |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Value | Description |
|-------|-------------|
| `application/json` | JSON request body (recommended) |
| `application/x-www-form-urlencoded` | Form requests |
| `multipart/form-data` | File uploads |

### Response Format

**Success**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Pagination**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**Error**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**Health check**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP Status Codes

| Status | Meaning |
|--------|---------|
| 200 | Success |
| 204 | OPTIONS preflight success |
| 400 | Invalid request parameters, unsupported API version |
| 401 | Unauthenticated, token expired, token IP/UA mismatch |
| 403 | Forbidden (XSS/path traversal/CSRF/SQL injection/Origin mismatch) |
| 404 | Resource not found |
| 429 | Too many requests (rate limit/login throttle/concurrent session limit) |
| 500 | Server error |
| 503 | Service degraded (DB or Redis unavailable) |

### Pagination Parameters

| Parameter | Default | Max | Description |
|-----------|---------|-----|-------------|
| `page` | 1 | — | Page number |
| `per_page` | 20 | 100 | Items per page (truncated when exceeded) |
| `sort` | `id` | — | Sort field (must be in whitelist) |

### Cache Strategy

| Endpoint | TTL | Tier |
|----------|-----|------|
| `/api/v1/platforms` | 1 hour | L1 memory → L2 APCu → L3 Redis |
| `/api/v1/accounts` + `/api/v1/accounts/:id` | 5 min | Same as above |
| `/api/v1/reports/summary` | 5 min | Same as above |
| `/api/v1/alerts/rules` | 2 min | Same as above |
| `/api/v1/alerts/unread-count` | 30 s | Same as above |

---

## Module 1: System

### GET /health — Health Check

```
GET /health
```

**Response**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) or `degraded` (503)
- No auth required, does not go through version routing

---

### GET /ping — Liveness Probe

```
GET /ping
```

**Response**: `{ "pong": true }`

---

### GET /docs — API Documentation

```
GET /docs
```

Returns an HTML API documentation page (no auth required).

---

### GET /api/v1/captcha/generate — Generate CAPTCHA

No auth required.

**Response**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- Token valid for 5 minutes
- Offset tolerance 5px

---

### POST /api/v1/captcha/verify — Verify CAPTCHA

No auth required.

**Request**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Response**: `{ "code": 0, "message": "验证通过" }`

---

## Module 2: Authentication

### POST /api/v1/auth/login — Login

No auth required.

**Request**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**Response**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- JWT token valid for 24 hours
- Token embeds IP + User-Agent hash
- 5 failures → Redis lockout for 15 minutes

---

### GET /api/v1/auth/me — Current User

**Request header**: `Authorization: Bearer <token>`

**Response**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/v1/auth/refresh — Refresh Token

**Request header**: `Authorization: Bearer <old_token>`

**Response**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- Old token automatically blacklisted
- Max 3 active tokens per user

---

## Module 3: Platforms & Accounts

### GET /api/v1/platforms — Platform List

No auth required. Cached for 1 hour.

**Response**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/v1/platforms/:code/oauth-url — OAuth Authorization URL

**Parameters**: `?redirect_uri=https://your-domain.com/callback`

**Response**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` must pass the SSRF whitelist check (`OAUTH_ALLOWED_REDIRECTS` env variable)

---

### POST /api/v1/platforms/:code/callback — OAuth Callback

**Request**: `{ "state": "...", "code": "..." }`

**Response**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/v1/accounts — Account List

Cached for 5 minutes.

**Parameters**:

| Parameter | Description |
|-----------|-------------|
| `platform` | Platform code filter |
| `page` | Page number |
| `per_page` | Items per page |

**Response**: paginated format; each item in `list` contains `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/v1/accounts/:id — Account Detail

Cached for 5 minutes.

---

### DELETE /api/v1/accounts/:id — Unbind Account

---

### POST /api/v1/accounts/:id/sync — Manual Sync

---

## Module 4: Campaigns

### GET /api/v1/campaigns — Campaign List

**Parameters**:

| Parameter | Description | Allowed Values |
|-----------|-------------|----------------|
| `platform` | Platform filter | juliang, meta, google... |
| `status` | Status filter | enabled, paused |
| `keyword` | Name search | Any text |
| `sort` | Sort field | id, name, platform, daily_budget, status, created_at |
| `page` | Page number | — |
| `per_page` | Items per page | ≤100 |

**Response**: paginated format + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/v1/campaigns — Create Campaign

**Request**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Response**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` unit: cents (20000 = ¥200.00)

---

### GET /api/v1/campaigns/:id — Campaign Detail

**Response**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/v1/campaigns/:id — Update Campaign

**Request**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/v1/campaigns/:id/toggle — Enable/Disable Campaign

**Request**: `{ "enabled": false }`

---

### POST /api/v1/campaigns/batch/toggle — Batch Toggle

**Request**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Response**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Module 5: Ad Groups

### GET /api/v1/ad-groups — Ad Group List

**Parameters**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/v1/ad-groups — Create Ad Group

**Request**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: optional, loads targeting JSON from the template and merges

### GET /api/v1/ad-groups/:id — Ad Group Detail

### PUT /api/v1/ad-groups/:id — Update Ad Group

### POST /api/v1/ad-groups/:id/toggle — Enable/Disable Ad Group

---

## Module 6: Creatives

### GET /api/v1/creatives — Creative List

**Parameters**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/v1/creatives/:id — Creative Detail

---

## Module 7: Reporting

### GET /api/v1/reports/summary — Dashboard Summary

Cached for 5 minutes.

**Parameters**: `date_start`, `date_end`

**Response**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/v1/reports/custom — Custom Report

**Parameters**:

| Parameter | Description |
|-----------|-------------|
| `dimensions[]` | Dimensions: date, platform, campaign |
| `metrics[]` | Metrics: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Start date |
| `date_end` | End date |
| `platform` | Platform filter |

---

### GET /api/v1/reports/export — Export Report

**Parameters**: `format=csv`, `date_start`, `date_end`, `metrics[]`

Returns a file download (CSV UTF-8 BOM or Excel .xls).

---

### GET /api/v1/reports/export-dashboard — Export Dashboard PDF

---

### GET /api/v1/reports/calendar — Campaign Calendar

**Parameters**: `date_start`, `date_end`, `platform`

**Response**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/v1/reports/budget-alerts — Budget Alerts

**Response**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/v1/reports/attribution — Attribution Analysis

**Parameters**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Response**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/v1/reports/attribution/models — Attribution Model List

**Response**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

5 models in total.

---

## Module 8: Alerts

### GET /api/v1/alerts/rules — Alert Rule List

Cached for 2 minutes.

**Parameters**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/v1/alerts/rules — Create Alert Rule

**Request**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/v1/alerts/rules/:id — Update Alert Rule

### DELETE /api/v1/alerts/rules/:id — Delete Alert Rule

### GET /api/v1/alerts/logs — Alert Logs

**Parameters**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/v1/alerts/logs/:id/acknowledge — Acknowledge Alert

### GET /api/v1/alerts/unread-count — Unread Alert Count

Cached for 30 seconds. Frontend polls every 30s.

---

## Module 9: Notifications

### GET /api/v1/notifications — Notification List

**Parameters**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/v1/notifications/unread-count — Unread Notification Count

### POST /api/v1/notifications/:id/read — Mark as Read

### POST /api/v1/notifications/read-all — Mark All as Read

---

## Module 10: Auto-Bidding

### GET /api/v1/bid-rules — Rule List

### POST /api/v1/bid-rules — Create Rule

**Request**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**Field Descriptions**:

| Field | Type | Description |
|-------|------|-------------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Monitored metric |
| condition | gt/gte/lt/lte | Trigger condition |
| threshold | decimal | Threshold |
| action_type | adjust_budget/toggle_pause/toggle_enable | Action type |
| adjust_step | int (cents) | Budget adjustment step (positive = increase, negative = decrease) |
| budget_min | int | Budget lower bound (cents) |
| budget_max | int | Budget upper bound (cents) |
| cooldown_minutes | int | Cooldown period (default 60) |

### PUT /api/v1/bid-rules/:id — Update Rule

### DELETE /api/v1/bid-rules/:id — Delete Rule

### GET /api/v1/bid-rules/logs — Bid History

**Parameters**: `rule_id`, `campaign_id`

---

## Module 11: Targeting Templates

### GET /api/v1/targeting-templates — Template List

**Parameters**: `platform`

### GET /api/v1/targeting-templates/:id — Template Detail

### POST /api/v1/targeting-templates — Create Template

**Request**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/v1/targeting-templates/:id — Update Template

### DELETE /api/v1/targeting-templates/:id — Delete Template

---

## Module 12: Asset Library

### GET /api/v1/assets — Asset List

**Parameters**: `type`(image/video), `page`, `per_page`

### POST /api/v1/assets/upload — Upload Asset

**Request**: `multipart/form-data`, field `file`

- Images: max 5 MB (jpeg/png/gif/webp)
- Videos: max 50 MB (mp4)

**Response**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- With CDN configured, `url` is assembled with the default provider's `cdn_domain` into a full HTTPS address

### POST /api/v1/assets/presign — Get Presigned Upload URL

**Request**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**Response**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- `key` format `Ymd/32-hex.ext`; pass it back to `/api/v1/assets/register` after direct upload
- For videos up to 50 MiB the client uploads directly to object storage; not available under the `local` driver

### POST /api/v1/assets/register — Register Directly Uploaded Asset

**Request**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**Response**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` strictly validated (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) against path traversal

### GET /api/v1/assets/:id — Asset Detail

### DELETE /api/v1/assets/:id — Delete Asset

---

## Admin Endpoints (port 8789)

### POST /api/v1/admin/login — Admin Login

**Request**: `{ "username": "admin", "password": "..." }`

**Response**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token stored in localStorage
- `csrf_token` must be sent in the `X-CSRF-Token` header on subsequent POST/PUT/DELETE requests

### GET /api/v1/admin/me — Current Admin

### POST /api/v1/admin/logout — Logout

### GET /api/v1/admin/users — User List

**Parameters**: `keyword`, `role_id`, `page`, `per_page`

`id` and `role_id` in the response are hashids-encoded.

### POST /api/v1/admin/users — Create User

### PUT /api/v1/admin/users/:id — Update User

### DELETE /api/v1/admin/users/:id — Disable User

### GET /api/v1/admin/users/roles — Role List

### GET /api/v1/admin/audit-logs — Audit Logs

**Parameters**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### CDN Provider Management (platform master tenant only, AdminMiddleware)

### GET /api/v1/admin/cdn/providers — Provider List

### POST /api/v1/admin/cdn/providers — Create Provider

**Request**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, S3 protocol) / `s3` (S3-compatible: AWS S3 / Cloudflare R2 / MinIO)
- Credentials (access_key/secret_key/cdn_token) encrypted at field level via Encryptable; responses return masked fields only

### PUT /api/v1/admin/cdn/providers/:id — Update Provider

### DELETE /api/v1/admin/cdn/providers/:id — Delete Provider (default auto-transfers to the next enabled provider)

### PUT /api/v1/admin/cdn/providers/:id/default — Set as Default

### PUT /api/v1/admin/cdn/providers/:id/toggle — Enable/Disable (disabling the default transfers it automatically)

### POST /api/v1/admin/cdn/providers/:id/test — Connectivity Test

**Response**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/v1/admin/cdn/providers/:id/purge — Purge CDN Cache

**Request**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- Requires `cdn_driver` and `cdn_domain`; `aliyun` is really implemented (OpenAPI signing), cloudflare/cloudfront pending

---

## Error Code Reference

| code | HTTP | Description |
|------|------|-------------|
| 0 | 200 | Success |
| 1 | 200/400 | General business error |
| 401 | 401 | Unauthenticated / token expired / IP/UA mismatch |
| 403 | 403 | Forbidden (security interception) |
| 404 | 404 | Resource not found |
| 422 | 422 | Parameter validation failed |
| 429 | 429 | Too many requests / login throttle / concurrency limit |
| 1001 | 200 | Auth failed (wrong username or password) |

---

## Security Interception Responses

When a request is intercepted by a security middleware, 403 is returned:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Rate Limit Response

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

The `Retry-After` header contains the remaining wait time in seconds.
