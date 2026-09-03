# Perbandingan Versi

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Versi | Lisensi | Cara memperoleh |
|------|------|----------|
| **Lite (Edisi Sederhana)** | Open source (MIT) | Repositori publik GitHub |
| **Standard** | Lisensi komersial | Hubungi erik@erik.xyz |
| **Full** | Lisensi komersial | Hubungi erik@erik.xyz |

---

## Perbandingan Fitur

### Fitur Dasar

| Fitur | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Autentikasi (login/refresh Token/pengguna saat ini) | ✅ | ✅ | ✅ |
| Manajemen platform (daftar 29 platform + OAuth) | ✅ | ✅ | ✅ |
| Manajemen akun (CRUD + sinkronisasi) | ✅ | ✅ | ✅ |
| Kampanye iklan (CRUD + start-stop + batch) | ✅ | ✅ | ✅ |
| Laporan (dasbor + kustom + ekspor CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Health check + dokumentasi API + kode verifikasi | ✅ | ✅ | ✅ |
| Sinkronisasi data (Campaign + Report) | ✅ | ✅ | ✅ |

### Manajemen Penayangan

| Fitur | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Ad group (CRUD + start-stop) | — | ✅ | ✅ |
| Kreatif iklan (daftar + detail) | — | ✅ | ✅ |
| Sinkronisasi data ad group/kreatif | — | ✅ | ✅ |

### Pemantauan dan Notifikasi

| Fitur | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Mesin aturan peringatan (7 metrik/4 kondisi/3 cakupan) | — | ✅ | ✅ |
| Catatan peringatan + konfirmasi + jumlah belum dibaca | — | ✅ | ✅ |
| Pusat notifikasi (daftar/dibaca/semua dibaca) | — | ✅ | ✅ |

### Fitur Lanjutan

| Fitur | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Mesin aturan penawaran otomatis (3 aksi/cooldown) | — | — | ✅ |
| Template penargetan audiens (JSON Schema umum) | — | — | ✅ |
| Pustaka materi iklan (unggah/galeri/pratinjau) | — | — | ✅ |
| Peringatan anggaran (peringatan tiga tahap 50/80/100%) | — | — | ✅ |
| Kalender penayangan (visualisasi Gantt) | — | — | ✅ |
| Atribusi lintas-platform (5 model/retrospektif 30 hari) | — | — | ✅ |

---

## Perbandingan Pertahanan Keamanan

| Item pertahanan | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| Whitelist CORS | ✅ | ✅ | ✅ |
| Header respons keamanan (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Route versi (/api/v1) | ✅ | ✅ | ✅ |
| Rate limit API (sliding window) | ✅ | ✅ | ✅ |
| Deteksi SQL injection (pencocokan pola) | ✅ | ✅ | ✅ |
| Filter input (strip_tags + trim) | ✅ | ✅ | ✅ |
| Enkripsi/dekripsi transmisi (X-Encrypted) | ✅ | ✅ | ✅ |
| Autentikasi JWT Bearer | ✅ | ✅ | ✅ |
| Deteksi serangan XSS (11 pola) | — | ✅ | ✅ |
| Deteksi path traversal (7 pola) | — | ✅ | ✅ |
| Deteksi Header injection | — | ✅ | ✅ |
| Batas ukuran Body (10 MiB) | — | ✅ | ✅ |
| Whitelist Content-Type | — | ✅ | ✅ |
| Identifikasi sumber klien (8 ujung) | — | ✅ | ✅ |
| Throttle login (5 kali→15 menit) | — | ✅ | ✅ |
| Pemantauan waktu respons (X-Response-Time) | — | ✅ | ✅ |
| Validasi Origin/Referer | — | — | ✅ |
| Anti serangan replay (Nonce+Timestamp) | — | — | ✅ |
| Batas sesi bersamaan (maks 3) | — | — | ✅ |
| Token CSRF (sisi Admin) | — | — | ✅ |
| Proteksi SSRF (whitelist OAuth) | — | — | ✅ |
| Desensitisasi data log | — | — | ✅ |
| Binding JWT IP/UA | — | — | ✅ |

---

## Perbandingan Rantai Middleware

### Sisi Service

| Lite (7 lapisan) | Standard (11 lapisan) | Full (15 lapisan) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Sisi Admin

| Lite (1 lapisan) | Standard (4 lapisan) | Full (5 lapisan) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Perbandingan Tugas Terjadwal

| Tugas | Frekuensi | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55 menit | ✅ | ✅ | ✅ |
| DataSyncTask | 10 menit | ✅ (hanya Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3 menit | ✅ | ✅ | ✅ |
| AlertCheckTask | 5 menit | — | ✅ | ✅ |
| BidCheckTask | 10 menit | — | — | ✅ |
| BudgetCheckTask | 15 menit | — | — | ✅ |

---

## Perbandingan Tabel Database

| Kategori | Nama tabel | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| Dasar | ads_tenants | ✅ | ✅ | ✅ |
| Akun | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| Penayangan | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| Peringatan | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| Notifikasi | ads_notifications | — | ✅ | ✅ |
| Penawaran | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| Penargetan | ads_targeting_templates | — | — | ✅ |
| Materi | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| Atribusi | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| Sistem | ads_sync_errors | ✅ | ✅ | ✅ |
| Manajemen | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Total** | | **8** | **13** | **19** |

---

## Perbandingan Halaman Frontend

### Vue Admin SPA

| Halaman | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dasbor | ✅ | ✅ | ✅ |
| Daftar akun + binding | ✅ | ✅ | ✅ |
| Kampanye iklan | ✅ | ✅ | ✅ |
| Ekspor laporan | ✅ | ✅ | ✅ |
| Manajemen pengguna | ✅ | ✅ | ✅ |
| Log audit | ✅ | ✅ | ✅ |
| Ad group | — | ✅ | ✅ |
| Kreatif iklan | — | ✅ | ✅ |
| Analisis laporan (ECharts) | — | ✅ | ✅ |
| Aturan peringatan | — | ✅ | ✅ |
| Catatan peringatan | — | ✅ | ✅ |
| Pusat notifikasi | — | ✅ | ✅ |
| Penawaran otomatis | — | — | ✅ |
| Pustaka materi | — | — | ✅ |
| Penyedia CDN | — | — | ✅ |
| Kalender penayangan | — | — | ✅ |
| Analisis atribusi | — | — | ✅ |
| **Total** | **7** | **13** | **18** |

### Flutter

| Halaman | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dasbor | ✅ | ✅ | ✅ |
| Kampanye iklan (daftar+detail) | ✅ | ✅ | ✅ |
| Laporan data | ✅ | ✅ | ✅ |
| Akun platform | ✅ | ✅ | ✅ |
| Manajemen peringatan | ✅ | ✅ | ✅ |
| Ad group | — | ✅ | ✅ |
| Kreatif iklan | — | ✅ | ✅ |
| Analisis laporan | — | ✅ | ✅ |
| Pusat notifikasi | — | ✅ | ✅ |
| Penawaran otomatis | — | — | ✅ |
| **Total** | **6** | **10** | **11** |

---

## Perbandingan Endpoint API

| Modul | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Sistem (health/ping/docs/captcha) | 6 | 6 | 6 |
| Autentikasi (login/me/refresh) | 3 | 3 | 3 |
| Platform (list/oauthUrl/callback) | 3 | 3 | 3 |
| Akun (index/show/destroy/sync) | 4 | 4 | 4 |
| Kampanye iklan (CRUD/toggle/batch) | 6 | 6 | 6 |
| Ad group (CRUD/toggle) | — | 5 | 5 |
| Kreatif (index/show) | — | 2 | 2 |
| Laporan (summary/custom/export×2) | 4 | 4 | 4 |
| Laporan (calendar/budget/attribution/models) | — | — | 4 |
| Peringatan (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| Notifikasi (index/unread/read/readAll) | — | 4 | 4 |
| Penawaran otomatis (CRUD + logs) | — | — | 5 |
| Template penargetan (CRUD) | — | — | 5 |
| Pustaka materi (index/upload/show/destroy/presign/register) | — | — | 6 |
| Penyedia CDN (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **Total** | **26** | **44** | **70** |

---

## Tumpukan Teknologi

Ketiga versi berbagi tumpukan teknologi terpadu:

| Lapisan | Teknologi |
|----|------|
| Framework backend | webman v2, PHP 8.2+ |
| Database | MySQL 8.0 (InnoDB, utf8mb4) |
| Cache | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Autentikasi | erikwang2013/jwt-webman |
| Generasi ID | erikwang2013/snowflake-php |
| Encoding ID | erikwang2013/hashids |
| Frontend | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Deployment | Docker + Nginx + Docker Compose |

---

## Jalur Upgrade

```
Lite (开源)
  │
  ├─→ 升级到 Standard (联系 erik@erik.xyz)
  │     │
  │     └─→ 新增: 广告组/创意管理、告警引擎、通知中心、
  │              AttackGuard/XSS/路径遍历/登录节流/响应时间监控
  │
  └─→ 升级到 Full (联系 erik@erik.xyz)
        │
        └─→ 新增: Standard 全部 + 自动出价、定向模板、素材库、
                  预算预警、投放日历、跨平台归因、防重放/并发限制/CSRF/SSRF
```
