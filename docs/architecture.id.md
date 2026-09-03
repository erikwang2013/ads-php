# Dokumen Desain Arsitektur

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. Ringkasan Sistem

Sistem manajemen periklanan multi-platform, terhubung dengan **29 platform iklan**, mencakup manajemen penayangan, laporan lintas-platform, pemantauan peringatan, penawaran otomatis, dan penargetan audiens. Mendukung tiga mode: SaaS multi-tenant, operasi agensi, dan penggunaan pribadi.

---

## 2. Arsitektur Deployment

```
                         ┌──────────────────────────┐
                         │  客户端                   │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 插件         │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 张表  │      │ 缓存/队列│      │ 搜索索引  │
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. Pipeline Pemrosesan Request

### 3.1 Sisi Service (15 lapisan middleware)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
  → RateLimitMiddleware       (Redis 滑动窗口 60次/60s)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟锁定)
  → SessionLimitMiddleware    (并发会话限制 最大3个活跃Token)
  → SqlGuardMiddleware        (SQL 注入模式检测)
  → ValidationMiddleware      (输入 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time 头 + 慢请求日志)
  → EncryptionMiddleware      (X-Encrypted 请求解密/响应加密)
  → AuthMiddleware            (JWT Bearer Token + IP/UA 绑定)
  → Controller
```

### 3.2 Sisi Admin (6 lapisan middleware)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. Struktur Direktori

```
ads-php/
├── service/                               # 业务 API 服务 :8788
│   ├── config/                            # 全局配置
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line 双模式)
│   │   ├── middleware.php                 # 11 层全局中间件
│   │   ├── exception.php                  # API 异常处理器
│   │   └── scout.php                      # ES 配置
│   ├── support/                           # 共享工具类 (erik\support)
│   │   ├── ApiResponse.php                # 统一 JSON 响应
│   │   ├── ControllerTrait.php            # 控制器公共 trait
│   │   ├── JwtService.php                 # JWT 包装 (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis 缓存
│   │   ├── HashidsService.php             # ID 加解密
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 异常渲染
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 层
│   │   │   ├── controller/v1/             # 14 个控制器
│   │   │   ├── middleware/                # 7 个中间件
│   │   │   ├── config/route.php           # 45+ 路由
│   │   ├── ads-platform/                  # 平台适配器核心
│   │   │   ├── adapter/                   # 29 个平台适配器
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + 性能索引
│   │   ├── ads-account/                   # OAuth 账户 + 平台账户
│   │   ├── ads-task/                      # 5 个 cron 任务
│   │   ├── ads-alert/                     # 告警引擎 + 通知
│   │   ├── ads-report/                    # 报表引擎 (CSV/Excel/PDF)
│   │   ├── ads-tenant/                    # 多租户
│   │   └── ads-storage/                   # Abstraksi penyimpanan (local/OSS/COS/S3) + penyedia CDN
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # 中间件测试
│   │   ├── Unit/Task/                     # 任务测试 (规划)
│   │   └── Integration/                   # 控制器集成测试
│   └── start.php                          # 入口
├── admin/                                 # 管理后台 :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 页面 (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 个 API 客户端
│   │       ├── stores/                    # 4 个 Pinia Store
│   │       └── components/                # ListPageLayout 等共享组件
│   └── config/                            # Admin 配置
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 功能页面 + Shell 布局
│   │       ├── config/menu_config.dart    # 两级菜单 + 面包屑
│   │       ├── router.dart                # GoRouter + ShellRoute + 路由守卫
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + 平台检测
│   └── harmonyos/                         # HarmonyOS (API Client 就绪)
├── docker/                                # Nginx 配置 + Dockerfiles
├── .github/workflows/                     # CI (语法→测试→TS→Docker) + CD (构建推送)
└── docs/                                  # 设计文档
```

---

## 5. Model Data

### 5.1 Klasifikasi Tabel

| Kategori | Nama tabel | Primary key | Kegunaan |
|------|------|------|------|
| Dasar | `ads_tenants` | BIGINT Snowflake | Multi-tenant |
| Akun | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | Akun platform OAuth |
| Hierarki penayangan | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Penayangan iklan |
| Laporan | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Metrik terpadu |
| Peringatan | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Pemantauan peringatan |
| Penawaran | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Penawaran otomatis |
| Penargetan | `ads_targeting_templates` | BIGINT Snowflake | Template audiens |
| Materi | `ads_assets` | BIGINT Snowflake | Pustaka materi kreatif |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | Konfigurasi penyedia CDN (kredensial terenkripsi per bidang) |
| Notifikasi | `ads_notifications` | BIGINT Snowflake | Notifikasi dalam aplikasi |
| Atribusi | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Pelacakan konversi + atribusi |
| Sistem | `ads_sync_errors` | BIGINT Snowflake | Error sinkronisasi |
| Manajemen | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + audit |

### 5.2 Konvensi Penamaan

- Prefiks tabel: `ads_`
- Primary key: `BIGINT UNSIGNED PRIMARY KEY` (tanpa auto-increment, Snowflake ID)
- Engine: InnoDB, charset: utf8mb4
- Timestamp: `created_at`, `updated_at` (DATETIME)

---

## 6. Arsitektur Keamanan

### 6.1 Lapisan Pertahanan

| Lapisan | Mekanisme | Cakupan |
|----|------|----------|
| Transmisi | Nginx (terminasi SSL) | Seluruh |
| Jaringan | Whitelist CORS + validasi Origin + HSTS | Service |
| Input | AttackGuard (XSS 11 pola/Path traversal 7 pola/Header injection) | Service + Admin |
| Injeksi | SQLGuard (deteksi pola SQL injection) | Service |
| Pembersihan | ValidationMiddleware (strip_tags) | Service |
| Autentikasi | JWT Bearer + bcrypt + binding IP/UA + rotasi refresh | Service |
| Autentikasi | Dual channel Session + JWT + Token CSRF | Admin |
| Otorisasi | RBAC (peran + JSON permission) | Admin |
| Throttle | RateLimit (sliding window) + LoginThrottle (5 kali→15 menit) | Service + Admin |
| Sesi | SessionLimit (maks 3 Token aktif) + blacklist | Service |
| Enkripsi | EncryptionMiddleware (transmisi) + Encryptable (penyimpanan) | Service |
| Replay | ReplayGuard (Nonce+Timestamp ±5min, sisi non-browser) | Service + klien |
| Ketahanan | CircuitBreaker (per-platform: 5 gagal → OPEN → 30s setengah terbuka) + GuardedAdapter (degradasi fast-fail) | Service |
| Audit | Jejak operasi (IP/UA/platform) | Admin |
| Desensitisasi | Penutupan field sensitif log (password/token/secret → ***) | Service |

### 6.2 Identifikasi Platform Klien

Melalui header `X-Client-Platform`:

| Nilai | Sumber |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | Aplikasi HarmonyOS |

---

## 7. Route Versi API

Nomor versi API ditetapkan di path URL (`/api/v1/...`), dan route terikat statis ke `plugin\ads_api\controller\v1\*`; tidak dikirim melalui header. Saat menambahkan versi baru, daftarkan grup route terpisah (mis. `/api/v2` → `controller\v2\*`).

```
请求: GET /api/v1/campaigns

路由 /api/v1/campaigns
  → controller\v1\CampaignController::index()
```

---

## 8. Penjadwalan Tugas Terjadwal

| Tugas | Cron | Fungsi |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | Refresh Token OAuth kedaluwarsa |
| DataSyncTask | `*/10 * * * *` | Sinkronkan Campaigns→AdGroups→Creatives→Reports→bersihkan cache |
| AlertCheckTask | `*/5 * * * *` | Evaluasi aturan peringatan, picu notifikasi |
| BidCheckTask | `*/10 * * * *` | Evaluasi aturan penawaran, eksekusi penyesuaian anggaran/start-stop |
| RetrySyncTask | `*/3 * * * *` | Coba ulang sinkronisasi gagal (maks 3 kali, backoff eksponensial) |

---

## 9. Integrasi Paket Erik Stack

| Paket | Lokasi integrasi | Kegunaan |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 Model (SnowflakeTrait) + admin helpers.php | Generasi primary key |
| `erikwang2013/hashids` | ApiResponse + 2 Admin Controller | Encoding ID |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Token autentikasi |
| `erikwang2013/encryption` | EncryptionMiddleware | Enkripsi/dekripsi transmisi |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Model | Enkripsi field DB |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | Pencarian ES |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Bendera negara |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Kode verifikasi slider |
| `hg/apidoc` | Anotasi → generasi dokumen (Web UI: :8788/apidoc) | Dokumentasi API |

---

## 10. Arsitektur High Concurrency

### 10.1 Lapisan Database

| Optimasi | Keterangan |
|------|------|
| Pemisahan baca-tulis | DB utama `shared`（tulis）+ replika read-only `read_replica`（kueri laporan/analisis） |
| Koneksi persisten | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` menghindari handshake TCP yang sering |
| Pemanasan koneksi | Eksekusi `SELECT 1` saat worker start, koneksi pool siap sebelum menerima request |

### 10.2 Lapisan Cache

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 Antrean Pesan

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 kanal: `sync` | `report` | `export` | `notification`

### 10.4 Skala Horizontal

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive**: 32 koneksi panjang direuse
- **failover**: `proxy_next_upstream` failover otomatis, 2 kali percobaan ulang
- **rate limit**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 CDN Aset Statis

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — file js/css pra-kompresi
- Integrasi CDN di lingkungan produksi (CloudFront/Aliyun CDN)

### 10.6 Akselerasi Aset CDN

Perakitan URL aset, strategi cache dan purge: lihat [Bab 12 Penyimpanan Aset & Akselerasi CDN](#12-penyimpanan-aset--akselerasi-cdn).

---

## 11. Deployment dan CI/CD

### Layanan Docker

| Layanan | Port | Image |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy

---

## 12. Penyimpanan Aset & Akselerasi CDN

### 12.1 Lapisan Abstraksi Penyimpanan

`service/plugin/ads-storage/` menyediakan fasad `Storage` terpadu + antarmuka `StorageDriver` (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge), berganti implementasi sesuai driver:

| driver | implementasi | kegunaan |
|--------|--------------|----------|
| `local` | LocalStorage | Default, lokal `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (protokol S3) |
| `s3` | S3CompatibleStorage | Kompatibel S3: AWS S3 / Cloudflare R2 / MinIO |

Distribusi mengutamakan penyedia default di DB (dikonfigurasi di admin), jika tidak ada kembali ke env/local.

### 12.2 Manajemen Penyedia CDN

Tabel baru `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- Kredensial (access_key/secret_key/cdn_token) dienkripsi per bidang via `Erikwang2013\Encryptable`; respons API hanya mengembalikan bidang tersamar
- Hanya tenant master platform (tenantId=1) yang dapat mengelola (AdminMiddleware); 8 endpoint di `/api/admin/cdn/providers`: daftar/buat/ubah/hapus/default/aktif-nonaktif/uji konektivitas/purge cache
- purge diimplementasikan nyata untuk cdn_driver `aliyun` (penandatanganan OpenAPI); cloudflare/cloudfront menyusul

### 12.3 Perakitan URL

`ads_assets.url` selalu menyimpan jalur relatif (`/uploads/assets/...`); saat dibaca, `cdn_domain` penyedia default ditambahkan di depan menjadi URL HTTPS lengkap (`https://{cdn_domain}/{url}`); tanpa CDN dikembalikan apa adanya.

### 12.4 Strategi Cache

| tipe | strategi |
|------|----------|
| gambar | cache panjang `immutable` (nama file acak, URL unik — aman) |
| video | cache pendek + dukungan Range (pemutaran bersegmen) |

Saat aset dihapus, URL-nya otomatis di-purge dari cache CDN.

### 12.5 Isolasi Jalur Multi-Tenant

Key aset memuat prefiks isolasi tenant dan dikelompokkan berdasarkan tenant_id; aset antar tenant berbeda saling tak terlihat.

### 12.6 Unggah Langsung Presign & Backfill

- `POST /api/assets/presign`: dapatkan URL unggah presign (klien unggah langsung ke object storage, mis. video 50 MiB); format `key`: `Ymd/32hex.ekstensi`
- `POST /api/assets/register`: mendaftarkan aset hasil unggah langsung; format key divalidasi ketat terhadap path traversal
- presign tidak tersedia pada driver `local` (tanpa penandatanganan object storage)
- `service/scripts/backfill-assets.php`: menyalin aset lokal yang ada ke object storage (`--dry-run` pratinjau); kolom `url` tetap

### 12.7 Jalur Origin

`service/config/static.php` mengaktifkan layanan file statis webman; `/uploads/assets` dilayani langsung via HTTP di 8788 sebagai jalur origin CDN.
