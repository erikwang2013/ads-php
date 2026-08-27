# Ads Platform — Sistem Manajemen Periklanan Multi-Platform

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Ringkasan

Terhubung dengan **29 platform iklan**, mengelola penayangan iklan dan laporan data lintas-platform secara terpadu, mendukung pemantauan peringatan, penawaran otomatis, dan akses multi-perangkat.

> Desain arsitektur → [docs/architecture.id.md](docs/architecture.id.md)  
> Modul fitur → [docs/features.id.md](docs/features.id.md)  
> Dokumentasi API → [docs/api.id.md](docs/api.id.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> Perbandingan versi → [docs/versions.id.md](docs/versions.id.md)（Lite open source / Standard & Full hubungi erik@erik.xyz）

### Platform yang Didukung

#### Domestik (16)
| Platform | Adaptor | Autentikasi |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| Baidu Marketing | Baidu | OAuth2 + Tanda tangan amplop |
| Taobao/Ali Mama | Taobao | OAuth2 + MD5 |
| Tencent Ads | Tencent | OAuth2 + nonce |
| Kuaishou Magnetic Engine | Kuaishou | OAuth2 URL parameter |
| Xiaohongshu Pugongying | Xiaohongshu | OAuth2 Bearer |
| Weibo Fans Tong | Weibo | OAuth2 Bearer |
| B站 Huahuo | Bilibili | OAuth2 Bearer |
| Youku Ads | Youku | OAuth2 + MD5 |
| Meituan Ads | Meituan | OAuth2 Bearer |
| Zhihu Ads | Zhihu | OAuth2 Bearer |
| 360 Promosi | Qihoo360 | API Key + Sign |
| Sogou Promosi | Sogou | API Key + Sign |
| Umeng | Umeng | API Key + MD5 |
| Jingdong Jingzhuntong | Jingdong | OAuth2 + MD5 |
| Pinduoduo Ads | Pinduoduo | OAuth2 + Sign kustom |

#### Internasional (13)
| Platform | Adaptor | Autentikasi |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL parameter |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## Tumpukan Teknologi

| Lapisan | Teknologi | Keterangan |
|----|------|------|
| Server | webman v2 + PHP 8.2+ | 7 plugin, 65+ endpoint API |
| Database | MySQL 8.0 | 28 tabel, prefiks ads_, primary key Snowflake BIGINT |
| Cache | Redis 7 | Cache tiga tingkat (L1 memori/L2 APCu/L3 Redis), penghitung pembatasan, Pub/Sub, antrean pesan |
| Pencarian | Elasticsearch | Sinkronisasi indeks otomatis webman-scout (sudah dikonfigurasi) |
| Panel Admin | webman-admin v2 + Vue 3 + TypeScript + Element Plus | Backend PHP (port 8789), SPA terhubung langsung ke API bisnis (port 8788), 19 halaman, visualisasi ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | Responsif PC/Mobile, tata letak Desktop Shell, 12 halaman |
| HarmonyOS | ArkTS + ArkUI | 6 halaman telah diimplementasikan, klien HTTP siap |
| Deployment | Docker + Nginx + GHCR | Docker Compose satu perintah, GitHub Actions build & push otomatis |

## Diagram Arsitektur

![Diagram Arsitektur Sistem](docs/diagrams/svg/architecture.id.svg)

### Diagram Alur Permintaan

![Diagram Alur Permintaan](docs/diagrams/svg/request-flow.id.svg)

### Diagram Modul Fungsi

![Diagram Modul Fungsi](docs/diagrams/svg/functional-modules.id.svg)

### Diagram Siklus Hidup Data

![Diagram Siklus Hidup Data](docs/diagrams/svg/data-lifecycle.id.svg)

> Versi lengkap memuat semua anotasi detail, pipeline sisi Admin, gantt tugas terjadwal, state machine cache → [docs/diagrams/](docs/diagrams/) |

> Penjelasan arsitektur detail, arsitektur keamanan, desain high-concurrency lihat [Dokumen Desain Arsitektur](docs/architecture.id.md) | Spesifikasi desain historis lihat [design.md](docs/superpowers/specs/design.id.md)

## Penjelasan Arsitektur

- **`service/`** — layanan API bisnis sisi pengguna webman v2, mendengarkan port **8788**. Menangani integrasi platform iklan, otorisasi OAuth, sinkronisasi data, mesin laporan, pemantauan peringatan, dan logika bisnis lainnya.
- **`admin/`** — panel admin independen webman-admin v2, mendengarkan port **8789**. Berisi backend PHP (autentikasi, manajemen pengguna, konfigurasi sistem) dan frontend SPA Vue 3.
- **Komunikasi panel admin dengan layanan bisnis** — SPA Vue terhubung langsung ke service API melalui axios (baseURL `/api`); rute khusus admin (`/api/admin/*`) dilayani oleh backend PHP admin (8789), Nginx membagi lalu lintas berdasarkan path.
- **Mode pengembangan** — Vite dev server (port 5173) mem-proxy `/api` ke service:8788; backend PHP admin menyediakan autentikasi session dan layanan statis SPA di 8789.
- **Mode produksi** — Nginx merutekan `/` ke admin:8789 (SPA panel admin), dan `/api/` ke service:8788 (API bisnis).

## Integrasi Erik Stack

| Paket | Kegunaan |
|----|------|
| `erikwang2013/snowflake-php` | Pembuatan ID Snowflake terdistribusi |
| `erikwang2013/hashids` | Enkripsi/dekripsi parameter ID API |
| `erikwang2013/jwt-webman` | Token autentikasi JWT |
| `erikwang2013/encryption` | Enkripsi/dekripsi data sensitif di lapisan API |
| `erikwang2013/encryptable` | Enkripsi/dekripsi otomatis tingkat kolom DB |
| `erikwang2013/webman-scout` | Sinkronisasi data Elasticsearch |
| `erikwang2013/season` | Identifikasi bendera negara |
| `erikwang2013/poster-php` | Kode verifikasi slider (perlindungan login) |
| `hg/apidoc` | Generasi dokumentasi API otomatis (anotasi + Web UI) |

## Internasionalisasi

Seluruh antarmuka mendukung peralihan bilingual **中文 (zh-CN)** / **English (en)**:

| Ujung | Teknologi | Cara peralihan |
|----|------|---------|
| Admin | vue-i18n v9 | Dropdown bahasa di TopBar, persistensi localStorage |
| Service API | `erik\support\I18n` | Header request Accept-Language / parameter `?lang=` |
| Flutter | AppLocalizations + Delegate | Deteksi otomatis bahasa sistem |
| HarmonyOS | StringResources | Peralihan `setLang()` |

## Keamanan

### Sisi Service (14 lapisan global + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware（lapisan rute）

### Sisi Admin (10 lapisan global + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck（lapisan rute）

### Ringkasan Kapabilitas Pertahanan (22 item)

| Kategori | Item pertahanan | Keterangan |
|------|--------|------|
| Deteksi input | XSS (11 pola) | script/iframe/event handler/javascript:/data: |
| | Path traversal (7 pola) | ../ / null byte / /etc/passwd / .env / .git |
| | Header injection | Deteksi CRLF |
| | Batas ukuran Body | 10 MiB |
| | Whitelist Content-Type | JSON/Form/Multipart/Plain |
| | SQL injection | Deteksi pola UNION/DROP/ALTER |
| Autentikasi | Pengikatan Token JWT | Verifikasi hash IP + User-Agent |
| | Refresh + blacklist Token | Token lama otomatis tidak berlaku |
| | Throttle login | 5 kali gagal → kunci 15 menit (Redis) |
| | Batas sesi bersamaan | Maksimal 3 Token aktif per pengguna |
| | Kode verifikasi | Kode verifikasi slider (berlaku 5 menit, toleransi 5px) |
| Validasi request | Whitelist CORS | Whitelist domain untuk lingkungan produksi |
| | Validasi Origin/Referer | Verifikasi sumber lintas-domain |
| | Token CSRF | Verifikasi session token di sisi Admin |
| | Anti serangan replay | Nonce + Timestamp ±5min (sisi non-browser) |
| | Rate limit API | Sliding window 60 kali/60s |
| | Proteksi SSRF | Whitelist redirect_uri OAuth |
| Header respons | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Anti clickjacking + paksaan HTTPS |
| | X-Content-Type-Options | nosniff |
| Perlindungan data | Enkripsi transmisi | EncryptionMiddleware (X-Encrypted) |
| | Enkripsi penyimpanan | Encryptable (tingkat kolom DB) |
| | Desensitisasi log | password/token/secret → \*\*\* |

### Diagram Arsitektur Keamanan

![Diagram Arsitektur Keamanan](docs/diagrams/svg/security.id.svg)

**Pertahanan berlapis**：Lapisan luar (Nginx) → Penjaga pintu masuk (5 lapisan middleware) → Autentikasi identitas (7 item) → Validasi input (4 item) → Kontrol frekuensi → Enkripsi data → Audit & penelusuran

**Autentikasi**：Server dan admin sama-sama menggunakan tabel `admin_users` + hash bcrypt, JWT 24 jam + rotasi refresh

**Audit**：Semua operasi mencatat IP / User-Agent / Client-Platform / detail operasi

**Konfirmasi kedua**：Operasi hapus/lepas/batch menggunakan pola "kata konfirmasi input"（`GlobalConfirm` + `useConfirmStore`）

---

## Fitur Lanjutan

| Fitur | Keterangan | Teknologi |
|------|------|------|
| Pustaka materi | Manajemen unggah gambar/video, pratinjau galeri, salin URL | AssetController + galeri Vue |
| Peringatan anggaran | Pelacakan real-time konsumsi anggaran harian, peringatan tiga tahap (50/80/100%) | BudgetAlertService + Cron 15 menit |
| Kalender penayangan | Grafik Gantt lintas-platform, tampilan bulan/minggu, pewarnaan per platform | CalendarService + Gantt Vue |
| Atribusi lintas-platform | 5 model atribusi (first/last/linear/time_decay/position_based), retrospektif 30 hari | AttributionEngine + ECharts |
| Ketahanan panggilan platform | State machine circuit breaker per platform (5 kegagalan → OPEN → probe half-open 30 detik), degradasi fast-fail, audit timeout 29 adaptor | CircuitBreaker + GuardedAdapter |

---

## High Concurrency

| Optimasi | Solusi | File |
|------|------|------|
| Pemisahan baca-tulis database | DB utama `shared` + replika read-only `read_replica`, SELECT otomatis dirutekan ke replika | `config/database.php` |
| Koneksi pool DB | Koneksi persisten `PDO::ATTR_PERSISTENT` + pemanasan inisialisasi zona waktu | `config/database.php` |
| Koneksi pool Redis | Koneksi persisten `persistent` + konfigurasi baca-tulis `readonly` | `config/redis.php` |
| Cache tiga tingkat | L1 memori proses → L2 memori bersama APCu → L3 Redis | `support/CacheService.php` |
| Antrean pesan asinkron | Redis List 4 kanal (sync/report/export/notification) | `support/AsyncJobService.php` |
| Rate limit bertingkat Nginx | 30r/s + burst 20 + 20 koneksi bersamaan + keepalive 32 | `docker/nginx/admin.conf` |
| Skala horizontal | Multi-instance upstream + failover + sticky session | `docker/nginx/admin.conf` |
| Percepatan CDN | Aset statis `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Memulai dengan Cepat

### Instalasi Web Satu Klik (disarankan)

Setelah layanan berjalan, buka `/install` di browser untuk masuk ke wizard instalasi:

```bash
# Mulai panel admin (port 8789)
cd admin && composer install && php start.php start

# Buka browser dan akses http://localhost:8789/install
# Isi informasi database dan akun admin di wizard instalasi, klik「Mulai Instalasi」
```

Wizard instalasi akan memandu Anda menyelesaikan di halaman web:
1. **Koneksi database** — isi host MySQL, port, nama database, username/password, mendukung tes koneksi
2. **Konfigurasi Redis** — isi informasi koneksi Redis (opsional)
3. **Akun admin** — atur username login, password, nama tampilan panel
4. **Instalasi satu klik** — otomatis buat database, jalankan `install.sql` untuk membuat 28 tabel dan menulis data seed, perbarui password admin

Setelah instalasi selesai, akses `/` untuk masuk ke panel admin, login menggunakan username dan password yang diatur.

### Docker (disarankan untuk produksi)

```bash
# Mulai semua layanan (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# Inisialisasi database (buat tabel + data seed)
make db-init

# Akses
# Panel admin: http://localhost
# Wizard instalasi: http://localhost/install
# API: http://localhost/api（Header: X-API-Version: v1）
```

### Pengembangan Lokal

```bash
# Server (port 8788)
cd service && composer install && php start.php start

# Panel admin (port 5173)
cd admin/public/web && npm install && npm run dev

# Aplikasi Flutter
cd apps/flutter && flutter run -d chrome  # PC Web
# Aplikasi HarmonyOS
# Gunakan DevEco Studio untuk membuka direktori apps/harmonyos
cd apps/flutter && flutter run -d android # Mobile

# Pemeriksaan TypeScript
cd admin/public/web && npx vue-tsc --noEmit   # Nol error
```

---

## Struktur Proyek

```
ads-php/
├── service/                           # Layanan bisnis sisi pengguna (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 endpoint, rute ber-versi)
│   │   │   ├── controller/v1/         # 17 controller
│   │   │   ├── middleware/            # 15 middleware
│   │   │   ├── config/route.php       # Definisi rute
│   │   │   └── route_helpers.php      # Fungsi bantuan versioned()
│   │   ├── ads-platform/              # Inti adaptor platform
│   │   │   ├── adapter/               # 29 adaptor platform
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # Migrasi SQL + indeks performa
│   │   ├── ads-account/               # Manajemen akun OAuth
│   │   ├── ads-task/                  # Penjadwalan tugas terjadwal (6 cron)
│   │   ├── ads-alert/                 # Mesin pemantauan peringatan + peringatan anggaran
│   │   ├── ads-report/                # Mesin laporan (CSV/Excel/PDF) + mesin atribusi + kalender penayangan
│   │   └── ads-tenant/                # Manajemen multi-tenant
│   ├── support/                       # Kelas utilitas Erik Stack
│   │   ├── ControllerTrait.php        # Trait umum controller
│   │   ├── JwtService.php             # Kelas pembungkus JWT
│   │   ├── CacheService.php           # Layanan cache Redis
│   │   ├── ExceptionHandler.php       # Penanganan exception API
│   │   └── ApiResponse.php            # Format respons terpadu
│   ├── config/                        # Konfigurasi global (DB/Redis/Log/Middleware)
│   ├── tests/                         # Pengujian PHPUnit (265 tests)
│   │   ├── Unit/                      # Unit test (Middleware, Task)
│   │   └── Integration/               # Integration test (Auth, Health)
│   └── start.php                      # Titik masuk layanan
├── admin/                             # Panel admin independen (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 halaman Vue
│   │   │   ├── dashboard/             # Dasbor (ECharts)
│   │   │   ├── campaign/              # Kampanye iklan
│   │   │   ├── adgroup/               # Grup iklan
│   │   │   ├── creative/              # Kreatif iklan
│   │   │   ├── report/                # Analisis laporan + ekspor
│   │   │   ├── alert/                 # Aturan peringatan + catatan
│   │   │   ├── notification/          # Pusat notifikasi
│   │   │   ├── bid/                   # Aturan penawaran otomatis
│   │   │   └── system/                # Manajemen pengguna + log audit
│   │   ├── api/                       # 9 klien API
│   │   ├── stores/                    # 4 Pinia Store
│   │   └── components/                # Komponen bersama (ListPageLayout dll.)
│   ├── app/                           # Backend PHP (controller/middleware)
│   └── config/                        # Konfigurasi Admin
├── apps/
│   ├── flutter/                       # Aplikasi Desktop Flutter
│   │   └── lib/
│   │       ├── features/              # 12 halaman fitur + tata letak Shell
│   │       ├── config/menu_config.dart # Konfigurasi menu dua tingkat
│   │       ├── router.dart            # GoRouter (ShellRoute + penjaga rute)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client siap)
├── docker/                            # Konfigurasi Docker & Nginx
├── .github/workflows/                 # CI (syntax→test→TS→Docker) + CD (build & push)
├── docs/                              # Dokumen desain, rencana implementasi, Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## Endpoint API

> Definisi seluruh endpoint API lihat [docs/api.id.md](docs/api.id.md)（termasuk contoh request/response, kode error, kebijakan rate limit）。
> Dokumentasi online hg/apidoc: setelah layanan berjalan, akses `http://127.0.0.1:8788/apidoc`

## Database

**Konvensi penamaan**: prefiks tabel `ads_`, primary key `BIGINT UNSIGNED PRIMARY KEY`（tanpa auto-increment, Snowflake ID）, engine InnoDB, charset utf8mb4

| Kategori | Nama tabel | Kegunaan |
|------|------|------|
| Dasar | `ads_tenants` | Multi-tenant |
| Akun | `ads_platform_accounts`, `ads_auth_tokens` | Akun platform OAuth |
| Penayangan | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | Hierarki penayangan iklan |
| Laporan | `ads_report_metrics`, `ads_report_extras` | Metrik laporan terpadu |
| Materi | `ads_assets` | Pustaka materi kreatif |
| Penargetan | `ads_targeting_templates` | Template penargetan audiens |
| Atribusi | `ads_conversions`, `ads_attribution_results` | Pelacakan konversi + hasil atribusi |
| Penawaran | `ads_bid_rules`, `ads_bid_logs` | Aturan penawaran otomatis + riwayat |
| Peringatan | `ads_alert_rules`, `ads_alert_logs` | Pemantauan peringatan |
| Notifikasi | `ads_notifications` | Notifikasi dalam aplikasi |
| Sistem | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Error sinkronisasi, RBAC, audit |

---

## Tugas Terjadwal

| Tugas | Frekuensi | Fungsi |
|------|------|------|
| TokenRefreshTask | Setiap 55 menit | Memindai Token OAuth kedaluwarsa, refresh otomatis |
| DataSyncTask | Setiap 10 menit | Menarik kampanye+ad group+kreatif+laporan setiap platform, menulis ke tabel terpadu, membersihkan cache |
| AlertCheckTask | Setiap 5 menit | Menelusuri aturan peringatan aktif, mengevaluasi ambang batas, memicu push |
| BidCheckTask | Setiap 10 menit | Menelusuri aturan penawaran otomatis, kueri metrik, mengeksekusi penyesuaian anggaran/start-stop |
| BudgetCheckTask | Setiap 15 menit | Menelusuri kampanye aktif, pelacakan konsumsi anggaran harian, peringatan tiga tahap (50/80/100%) |
| RetrySyncTask | Setiap 3 menit | Mencoba ulang tugas sinkronisasi yang gagal (maks 3 kali, backoff eksponensial) |

---

## Pengujian

```bash
cd service && ./vendor/bin/phpunit
# 265 test / 717 assertion
```

**Cakupan**: Middleware (Version/SQLGuard/SecurityHeaders) · Objek data (CampaignData/FieldMapping/Hashids) · Mesin (ReportBuilder/AdapterRegistry) · Integration test (Auth/Health)

```bash
# Pemeriksaan TypeScript
cd admin/public/web && npx vue-tsc --noEmit   # Nol error

# Analisis Dart
cd apps/flutter && dart analyze   # Nol error
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): pipeline otomatis — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): pemicu manual — **Docker Buildx → push GHCR (service/admin/admin-php) → notifikasi deployment**

`.github/dependabot.yml` memperbarui dependensi Composer + npm + Docker secara otomatis setiap minggu.

---

## Skills

`docs/skills/` — 11 skill proyek yang dapat digunakan kembali:

| Skill | Keterangan |
|------|------|
| `adapter-generator` | Menghasilkan adaptor platform iklan baru (template 14 metode) |
| `migration-generator` | Menghasilkan file migrasi SQL (prefiks ads_ + PK BIGINT) |
| `erik-stack` | Panduan integrasi 8 paket Erik Stack |
| `admin-page-generator` | Menghasilkan halaman panel admin Vue3 |
| `api-endpoint` | Menambahkan endpoint RESTful API |
| `tdd-workflow` | Alur verifikasi TDD (test→implementasi→syntax→TypeScript→commit) |
| `security-middleware` | Menambahkan lapisan middleware keamanan (spesifikasi antarmuka + registrasi + referensi rantai yang ada) |
| `version-split` | Pemisahan tiga versi Lite/Standard/Full (langkah operasi + pembaruan konfigurasi) |
| `cache-strategy` | Strategi cache tiga tingkat (L1 memori/L2 APCu/L3 Redis + saran TTL) |
| `attribution-setup` | Mesin atribusi lintas-platform (5 model + panggilan API + persiapan data) |
| `high-concurrency` | 8 optimasi high concurrency (baca-tulis split/pool koneksi/antrean pesan/skala horizontal/CDN) |


## Open Source Butuh Dukungan

| WeChat | Alipay |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### Global Transfer Donation

**收款人信息 (Beneficiary)**

| 字段 | 值 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**收款银行 (Receiving Bank) — ZA Bank**

| 字段 | 值 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需，Correspondent Bank）**：此为代理（中转）银行信息，非收款银行信息，请向汇款银行查询是否需要提供。
>
> - **港元、人民币及美元**：Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **其他币种**：THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

---

## Lisensi

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
