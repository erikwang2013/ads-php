# Dokumentasi Antarmuka API

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **Dokumentasi online hg/apidoc**: setelah layanan berjalan, akses `http://127.0.0.1:8788/apidoc`（peralihan ganda aplikasi Service + Admin）  
> File konfigurasi: `service/config/plugin/hg/apidoc/app.php`

---

## Konvensi Umum

### Base URL

```
http://your-domain.com/api/v1
```

> Nomor versi API ditetapkan di dalam jalur URL (saat ini `v1`) dan tidak dikirim melalui Header; versi mayor berikutnya seperti `/api/v2` mengikuti aturan yang sama.

### Headers Wajib

| Header | Nilai | Keterangan |
|--------|----|------|
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Ujung sumber operasi（wajib） |
| `Authorization` | `Bearer <token>` | Token autentikasi JWT（wajib kecuali login/daftar platform/health check） |

### Header Anti-Replay (sisi non-browser)

| Header | Keterangan |
|--------|------|
| `X-Nonce` | String acak（unik untuk setiap request） |
| `X-Timestamp` | Timestamp Unix detik（jendela ±5 menit） |

### Headers Opsional

| Header | Keterangan |
|--------|------|
| `X-Tenant-Id` | ID tenant（mode multi-tenant） |
| `X-Encrypted` | `1` = body request perlu didekripsi, body respons perlu dienkripsi |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Nilai | Keterangan |
|----|------|
| `application/json` | Body request JSON（disarankan） |
| `application/x-www-form-urlencoded` | Request form |
| `multipart/form-data` | Unggah file |

### Format Respons

**Sukses**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Paginasi**:
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

### Kode Status HTTP

| Kode status | Arti |
|--------|------|
| 200 | Sukses |
| 204 | Preflight OPTIONS sukses |
| 400 | Parameter request salah, versi API tidak didukung |
| 401 | Belum terautentikasi, Token kedaluwarsa, IP/UA Token tidak cocok |
| 403 | Akses dilarang（XSS/path traversal/CSRF/SQL injection/Origin tidak cocok） |
| 404 | Resource tidak ada |
| 429 | Terlalu banyak request（rate limit/throttle login/batas sesi bersamaan） |
| 500 | Error server |
| 503 | Degradasi layanan（DB atau Redis tidak tersedia） |

### Parameter Paginasi

| Parameter | Nilai default | Nilai maksimum | Keterangan |
|------|--------|--------|------|
| `page` | 1 | — | Nomor halaman |
| `per_page` | 20 | 100 | Jumlah per halaman（lebih dari otomatis dipotong） |
| `sort` | `id` | — | Field pengurutan（harus di dalam whitelist） |

### Strategi Cache

| Endpoint | TTL | Lapisan |
|------|-----|-----|
| `/api/v1/platforms` | 1 jam | L1 memori → L2 APCu → L3 Redis |
| `/api/v1/accounts` + `/api/v1/accounts/:id` | 5 menit | sama seperti di atas |
| `/api/v1/reports/summary` | 5 menit | sama seperti di atas |
| `/api/v1/alerts/rules` | 2 menit | sama seperti di atas |
| `/api/v1/alerts/unread-count` | 30 detik | sama seperti di atas |

---

## Modul 1: Sistem

### GET /health — Health Check

```
GET /health
```

**Respons**:
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

- `status`: `healthy` (200) atau `degraded` (503)
- Tanpa syarat autentikasi, tidak melalui route versi

---

### GET /ping — Cek Ketersediaan

```
GET /ping
```

**Respons**: `{ "pong": true }`

---

### GET /docs — Dokumentasi API

```
GET /docs
```

Mengembalikan halaman dokumentasi API dalam format HTML（tanpa autentikasi）。

---

### GET /api/v1/captcha/generate — Generate Kode Verifikasi

Tanpa autentikasi.

**Respons**:
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

- Masa berlaku token 5 menit
- Toleransi offset 5px

---

### POST /api/v1/captcha/verify — Verifikasi Kode Verifikasi

Tanpa autentikasi.

**Request**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Respons**: `{ "code": 0, "message": "验证通过" }`

---

## Modul 2: Autentikasi

### POST /api/v1/auth/login — Login

Tanpa autentikasi.

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

**Respons**:
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

- Masa berlaku Token JWT 24 jam
- Token menyematkan hash IP + User-Agent
- 5 kali gagal → kunci Redis 15 menit

---

### GET /api/v1/auth/me — Pengguna Saat Ini

**Header request**: `Authorization: Bearer <token>`

**Respons**:
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

**Header request**: `Authorization: Bearer <old_token>`

**Respons**:
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

- Token lama otomatis ditambahkan ke blacklist
- Maksimal 3 Token aktif per pengguna

---

## Modul 3: Platform & Akun

### GET /api/v1/platforms — Daftar Platform

Tanpa autentikasi. Cache 1 jam.

**Respons**:
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

### GET /api/v1/platforms/:code/oauth-url — URL Otorisasi OAuth

**Parameter**: `?redirect_uri=https://your-domain.com/callback`

**Respons**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` harus lolos validasi whitelist SSRF（variabel lingkungan `OAUTH_ALLOWED_REDIRECTS`）

---

### POST /api/v1/platforms/:code/callback — Callback OAuth

**Request**: `{ "state": "...", "code": "..." }`

**Respons**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/v1/accounts — Daftar Akun

Cache 5 menit.

**Parameter**:

| Parameter | Keterangan |
|------|------|
| `platform` | Filter kode platform |
| `page` | Nomor halaman |
| `per_page` | Jumlah per halaman |

**Respons**: format paginasi, setiap item di list berisi `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/v1/accounts/:id — Detail Akun

Cache 5 menit.

---

### DELETE /api/v1/accounts/:id — Lepas Ikatan Akun

---

### POST /api/v1/accounts/:id/sync — Sinkronisasi Manual

---

## Modul 4: Kampanye Iklan

### GET /api/v1/campaigns — Daftar Kampanye

**Parameter**:

| Parameter | Keterangan | Nilai opsional |
|------|------|--------|
| `platform` | Filter platform | juliang, meta, google... |
| `status` | Filter status | enabled, paused |
| `keyword` | Pencarian nama | teks bebas |
| `sort` | Field pengurutan | id, name, platform, daily_budget, status, created_at |
| `page` | Nomor halaman | — |
| `per_page` | Jumlah per halaman | ≤100 |

**Respons**: format paginasi + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/v1/campaigns — Buat Kampanye

**Request**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Respons**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- Satuan `daily_budget`: sen（20000 = ¥200.00）

---

### GET /api/v1/campaigns/:id — Detail Kampanye

**Respons**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/v1/campaigns/:id — Perbarui Kampanye

**Request**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/v1/campaigns/:id/toggle — Start-Stop Kampanye

**Request**: `{ "enabled": false }`

---

### POST /api/v1/campaigns/batch/toggle — Start-Stop Batch

**Request**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Respons**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Modul 5: Ad Group

### GET /api/v1/ad-groups — Daftar Ad Group

**Parameter**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/v1/ad-groups — Buat Ad Group

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

- `targeting_template_id`: opsional, memuat targeting JSON dari template penargetan dan menggabungkannya

### GET /api/v1/ad-groups/:id — Detail Ad Group

### PUT /api/v1/ad-groups/:id — Perbarui Ad Group

### POST /api/v1/ad-groups/:id/toggle — Start-Stop Ad Group

---

## Modul 6: Kreatif

### GET /api/v1/creatives — Daftar Kreatif

**Parameter**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/v1/creatives/:id — Detail Kreatif

---

## Modul 7: Laporan

### GET /api/v1/reports/summary — Ringkasan Dasbor

Cache 5 menit.

**Parameter**: `date_start`, `date_end`

**Respons**:
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

### GET /api/v1/reports/custom — Laporan Kustom

**Parameter**:

| Parameter | Keterangan |
|------|------|
| `dimensions[]` | Dimensi: date, platform, campaign |
| `metrics[]` | Metrik: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Tanggal mulai |
| `date_end` | Tanggal akhir |
| `platform` | Filter platform |

---

### GET /api/v1/reports/export — Ekspor Laporan

**Parameter**: `format=csv`, `date_start`, `date_end`, `metrics[]`

Mengembalikan unduhan file（CSV UTF-8 BOM atau Excel .xls）。

---

### GET /api/v1/reports/export-dashboard — Ekspor Dasbor PDF

---

### GET /api/v1/reports/calendar — Kalender Penayangan

**Parameter**: `date_start`, `date_end`, `platform`

**Respons**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/v1/reports/budget-alerts — Peringatan Anggaran

**Respons**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/v1/reports/attribution — Analisis Atribusi

**Parameter**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Respons**:
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

### GET /api/v1/reports/attribution/models — Daftar Model Atribusi

**Respons**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

Total 5 model.

---

## Modul 8: Peringatan

### GET /api/v1/alerts/rules — Daftar Aturan Peringatan

Cache 2 menit.

**Parameter**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/v1/alerts/rules — Buat Aturan Peringatan

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

### PUT /api/v1/alerts/rules/:id — Perbarui Aturan Peringatan

### DELETE /api/v1/alerts/rules/:id — Hapus Aturan Peringatan

### GET /api/v1/alerts/logs — Catatan Peringatan

**Parameter**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/v1/alerts/logs/:id/acknowledge — Konfirmasi Peringatan

### GET /api/v1/alerts/unread-count — Jumlah Peringatan Belum Dibaca

Cache 30 detik. Frontend polling 30 detik.

---

## Modul 9: Notifikasi

### GET /api/v1/notifications — Daftar Notifikasi

**Parameter**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/v1/notifications/unread-count — Jumlah Notifikasi Belum Dibaca

### POST /api/v1/notifications/:id/read — Tandai Dibaca

### POST /api/v1/notifications/read-all — Semua Dibaca

---

## Modul 10: Penawaran Otomatis

### GET /api/v1/bid-rules — Daftar Aturan

### POST /api/v1/bid-rules — Buat Aturan

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

**Keterangan field**:

| Field | Tipe | Keterangan |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Metrik yang dipantau |
| condition | gt/gte/lt/lte | Kondisi pemicu |
| threshold | decimal | Ambang batas |
| action_type | adjust_budget/toggle_pause/toggle_enable | Tipe aksi |
| adjust_step | int (sen) | Langkah penyesuaian anggaran（positif=tambah, negatif=kurang） |
| budget_min | int | Batas bawah anggaran（sen） |
| budget_max | int | Batas atas anggaran（sen） |
| cooldown_minutes | int | Waktu cooldown（default 60） |

### PUT /api/v1/bid-rules/:id — Perbarui Aturan

### DELETE /api/v1/bid-rules/:id — Hapus Aturan

### GET /api/v1/bid-rules/logs — Riwayat Penawaran

**Parameter**: `rule_id`, `campaign_id`

---

## Modul 11: Template Penargetan

### GET /api/v1/targeting-templates — Daftar Template

**Parameter**: `platform`

### GET /api/v1/targeting-templates/:id — Detail Template

### POST /api/v1/targeting-templates — Buat Template

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

### PUT /api/v1/targeting-templates/:id — Perbarui Template

### DELETE /api/v1/targeting-templates/:id — Hapus Template

---

## Modul 12: Pustaka Materi

### GET /api/v1/assets — Daftar Materi

**Parameter**: `type`(image/video), `page`, `per_page`

### POST /api/v1/assets/upload — Unggah Materi

**Request**: `multipart/form-data`, field `file`

- Gambar: maksimal 5 MB (jpeg/png/gif/webp)
- Video: maksimal 50 MB (mp4)

**Respons**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- Dengan CDN dikonfigurasi, `url` dirakit dengan `cdn_domain` penyedia default menjadi alamat HTTPS lengkap

### POST /api/v1/assets/presign — Dapatkan URL unggah presign

**Permintaan**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**Respons**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- Format `key`: `Ymd/32hex.ekstensi`; kirim kembali ke `/api/v1/assets/register` setelah unggah langsung
- Untuk video hingga 50 MiB klien mengunggah langsung ke object storage; tidak tersedia pada driver `local`

### POST /api/v1/assets/register — Daftarkan aset hasil unggah langsung

**Permintaan**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**Respons**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` divalidasi ketat (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) — cegah path traversal

### GET /api/v1/assets/:id — Detail Materi

### DELETE /api/v1/assets/:id — Hapus Materi

---

## Endpoint Admin (port 8789)

### POST /api/v1/admin/login — Login Admin

**Request**: `{ "username": "admin", "password": "..." }`

**Respons**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token disimpan ke localStorage
- `csrf_token` perlu dibawa di header `X-CSRF-Token` untuk request POST/PUT/DELETE berikutnya

### GET /api/v1/admin/me — Admin Saat Ini

### POST /api/v1/admin/logout — Keluar

### GET /api/v1/admin/users — Daftar Pengguna

**Parameter**: `keyword`, `role_id`, `page`, `per_page`

`id` dan `role_id` di respons menggunakan encoding hashids.

### POST /api/v1/admin/users — Buat Pengguna

### PUT /api/v1/admin/users/:id — Perbarui Pengguna

### DELETE /api/v1/admin/users/:id — Nonaktifkan Pengguna

### GET /api/v1/admin/users/roles — Daftar Peran

### GET /api/v1/admin/audit-logs — Log Audit

**Parameter**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### Manajemen Penyedia CDN (hanya tenant master platform tenant 1, AdminMiddleware)

### GET /api/v1/admin/cdn/providers — Daftar penyedia

### POST /api/v1/admin/cdn/providers — Buat penyedia

**Permintaan**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, protokol S3) / `s3` (kompatibel S3: AWS S3 / Cloudflare R2 / MinIO)
- Kredensial (access_key/secret_key/cdn_token) dienkripsi per bidang via Encryptable; respons hanya berisi bidang tersamar

### PUT /api/v1/admin/cdn/providers/:id — Ubah penyedia

### DELETE /api/v1/admin/cdn/providers/:id — Hapus (default otomatis pindah ke penyedia enabled berikutnya)

### PUT /api/v1/admin/cdn/providers/:id/default — Tetapkan sebagai default

### PUT /api/v1/admin/cdn/providers/:id/toggle — Aktif/Nonaktif (menonaktifkan default mengalihkannya otomatis)

### POST /api/v1/admin/cdn/providers/:id/test — Uji konektivitas

**Respons**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/v1/admin/cdn/providers/:id/purge — Purge cache CDN

**Permintaan**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- Perlu `cdn_driver` dan `cdn_domain`; `aliyun` diimplementasikan nyata (penandatanganan OpenAPI), cloudflare/cloudfront menyusul

---

## Referensi Kode Error

| code | HTTP | Keterangan |
|------|------|------|
| 0 | 200 | Sukses |
| 1 | 200/400 | Error bisnis umum |
| 401 | 401 | Belum terautentikasi / Token kedaluwarsa / IP/UA tidak cocok |
| 403 | 403 | Akses dilarang（intersepsi keamanan） |
| 404 | 404 | Resource tidak ada |
| 422 | 422 | Validasi parameter gagal |
| 429 | 429 | Terlalu banyak request / throttle login / batas bersamaan |
| 1001 | 200 | Autentikasi gagal（username atau password salah） |

---

## Respons Intersepsi Keamanan

Ketika request diintersepsi middleware keamanan, kembalikan 403:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Respons Rate Limit

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

Header `Retry-After` berisi sisa detik tunggu.
