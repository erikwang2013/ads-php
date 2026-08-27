# Dokumen Desain Fitur

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Seluruh definisi antarmuka API（request/response/parameter）lihat [api.id.md](api.id.md)。

---

## Ringkasan Modul

| # | Modul | Controller/Layanan | Jumlah route API | Halaman Vue |
|---|------|--------|-----------|----------|
| 1 | Autentikasi & otorisasi | AuthController | 3 | LoginPage |
| 2 | Manajemen platform | PlatformController | 3 | — |
| 3 | Manajemen akun | AccountController | 5 | AccountList, AccountBind |
| 4 | Kampanye iklan | CampaignController | 6 | CampaignList |
| 5 | Ad group | AdGroupController | 5 | AdGroupList |
| 6 | Kreatif iklan | CreativeController | 2 | CreativeList |
| 7 | Laporan data | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Pemantauan peringatan | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Pusat notifikasi | NotificationController | 4 | NotificationList |
| 10 | Penawaran otomatis | BidRuleController | 5 | BidRuleList |
| 11 | Template penargetan | TargetingTemplateController | 5 | — |
| 12 | Manajemen sistem | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Sinkronisasi data | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Pustaka materi | AssetController | 4 | AssetGallery |
| 15 | Peringatan anggaran | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Kalender penayangan | CalendarService | 1 | CampaignCalendar |
| 17 | Atribusi lintas-platform | AttributionEngine | 2 | AttributionReport |
| 18 | Health check | HealthController | 2 | — |
| 19 | Kode verifikasi | CaptchaController | 2 | — |
| 20 | Dokumentasi API | DocController | 1 | — |

**Total**: 20 modul, 65+ route, 18 halaman Vue

---

## Modul 1: Autentikasi & Otorisasi

- Pemeriksaan kode verifikasi (opsional)
- Kueri tabel `admin_users`
- Verifikasi bcrypt `password_verify()`
- Generasi Token JWT (TTL 24 jam)
- Token lama otomatis ditambahkan ke blacklist
- Ekstrak `uid` dari Token untuk kueri informasi pengguna

Antarmuka: Login / Refresh Token / Pengguna saat ini → [api.id.md modul 2](api.id.md#模块-2-认证)

---

## Modul 2-3: Manajemen Platform & Akun

- Daftar platform di-cache 1 jam (Redis), terintegrasi emoji bendera Season
- Alur OAuth: generate state acak → buat URL otorisasi → proses callback → simpan Token
- Daftar/detail akun di-cache 5 menit

Antarmuka: Daftar platform / OAuth / CRUD akun + sinkronisasi → [api.id.md modul 3](api.id.md#模块-3-平台--账户)

---

## Modul 4-6: Hierarki Penayangan Iklan

### Struktur Data

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- Pembuatan kampanye melalui adaptor platform + menulis ke lokal
- Mendukung filter per platform/status/kata kunci, daftar berisi ringkasan hari ini
- Pembuatan ad group mendukung `targeting_template_id` untuk memuat template penargetan

Antarmuka: Kampanye / Ad group / Kreatif → [api.id.md modul 4-6](api.id.md#模块-4-广告计划)

---

## Modul 7: Laporan Data

- Ringkasan dasbor di-cache 5 menit: 8 kartu metrik KPI + grafik garis tren harian + grafik batang platform
- Dimensi laporan kustom: date, platform, campaign
- Metrik: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Format ekspor: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (cetak HTML)

Antarmuka: Ringkasan / Kustom / Ekspor → [api.id.md modul 7](api.id.md#模块-7-报表)

---

## Modul 8: Pemantauan Peringatan

### Alur Evaluasi AlertEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Kanal Notifikasi

| Kanal | Status | Implementasi |
|------|------|------|
| web | ✅ | Tulis ke erik_notifications |
| email | placeholder | Stub echo |
| sms | placeholder | Stub echo |
| Redis pub/sub | ✅ | Push JSON kanal `alert:new` |

Antarmuka: CRUD aturan / catatan peringatan / konfirmasi / jumlah belum dibaca → [api.id.md modul 8](api.id.md#模块-8-告警)

---

## Modul 9: Pusat Notifikasi

- Polling 30 detik Pinia store di frontend
- Ikon lonceng sidebar + badge angka belum dibaca

Antarmuka: Daftar / jumlah belum dibaca / tandai dibaca / semua dibaca → [api.id.md modul 9](api.id.md#模块-9-通知)

---

## Modul 10: Mesin Penawaran Otomatis

### Alur Evaluasi BidEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Field Aturan

| Field | Tipe | Keterangan |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Metrik yang dipantau |
| condition | gt/gte/lt/lte | Kondisi pemicu |
| threshold | DECIMAL(12,2) | Ambang batas |
| scope | tenant/platform/campaign | Cakupan |
| action_type | adjust_budget/toggle_pause/toggle_enable | Aksi |
| adjust_step | INT (分) | Langkah penyesuaian anggaran (positif=tambah, negatif=kurang) |
| budget_min, budget_max | BIGINT | Batas anggaran |
| cooldown_minutes | INT | Periode cooldown |

Antarmuka: CRUD aturan / riwayat penawaran → [api.id.md modul 10](api.id.md#模块-10-自动出价)

---

## Modul 11: Template Penargetan Audiens

### Integrasi ke Ad Group

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### JSON Schema Umum

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

Antarmuka: CRUD template → [api.id.md modul 11](api.id.md#模块-11-定向模板)

---

## Modul 12: Manajemen Sistem (Admin)

- Daftar pengguna di-encoding dengan ID hashids
- Pembuatan pengguna dengan password hash bcrypt
- Nonaktifkan pengguna adalah soft disable (status=0)

Field log audit: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

Antarmuka: Manajemen pengguna / log audit / peran → [api.id.md endpoint Admin](api.id.md#admin-端点端口-8789)

---

## Modul 13: Sinkronisasi Data

### Alur DataSyncTask (setiap 10 menit)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Format Respons

### Sukses
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Paginasi
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Error
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Modul 14: Pustaka Materi Iklan

- Tipe yang didukung: image/jpeg, image/png, image/gif, image/webp, video/mp4
- Penyimpanan file: `public/uploads/assets/`
- Frontend: galeri grid + unggah drag-drop + pratinjau gambar + pemutaran video + salin URL

Antarmuka: Unggah / daftar / detail / hapus → [api.id.md modul 12](api.id.md#模块-12-素材库)

---

## Modul 15: Peringatan Anggaran

- Peringatan tiga tahap: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask dieksekusi setiap 15 menit
- Deduplikasi: kampanye yang sama di level yang sama hanya dinotifikasi sekali per hari
- Tulis ke tabel `erik_notifications`

Antarmuka: Peringatan anggaran → [api.id.md modul 7](api.id.md#模块-7-报表)

---

## Modul 16: Kalender Penayangan

- Agregasi jadwal kampanye per tanggal
- Grafik Gantt frontend: sumbu x tanggal, sumbu y kampanye, dibedakan warna per platform
- Mendukung peralihan tampilan bulan/minggu

Antarmuka: Kalender penayangan → [api.id.md modul 7](api.id.md#模块-7-报表)

---

## Modul 17: Atribusi Lintas-Platform

### Model Atribusi

| Model | Algoritma |
|------|------|
| first_touch | Titik sentuh pertama 100% |
| last_touch | Titik sentuh terakhir 100% |
| linear | Semua titik sentuh dibagi rata (1/N) |
| time_decay | e^(-λ×Δt), waktu paruh 7 hari |
| position_based | Awal 40% + akhir 40% + tengah 20% |

- Jendela retrospektif: 30 hari
- Sumber titik sentuh: `erik_report_metrics` (klik > 0)
- Hasil ditulis ke `erik_attribution_results`
- Frontend: peralihan model AttributionReport.vue + kartu statistik + grafik batang ECharts + tabel detail

### Tabel Data

| Tabel | Field |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

Antarmuka: Analisis atribusi / daftar model → [api.id.md modul 7](api.id.md#模块-7-报表)

### Health Check
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## Modul 18: Ketahanan Panggilan Platform (Circuit Breaker / Degradasi)

### State Machine Circuit Breaker

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — state machine per-platform:

| State | Pemicu | Perilaku |
|-------|--------|----------|
| CLOSED | Normal | Panggilan diloloskan |
| OPEN | 5 kegagalan beruntun | Fast-fail, lewati platform |
| HALF_OPEN | Setelah 30s cooldown | Satu permintaan probe |
| CLOSED | Probe berhasil | Pulih, penghitung direset |
| OPEN | Probe gagal lagi | Putus lagi |

### Proxy GuardedAdapter

- `AdapterRegistry::get()` mengembalikan proxy GuardedAdapter; 14 titik panggilan tanpa perubahan
- Saat OPEN melempar `CircuitBreakerOpenException` (fast-fail); lapisan tugas menangkap dan menyerap = degradasi melewati platform
- Metode Generator: iterasi lengkap → success, terputus → failure

### Verifikasi Timeout

- 29 adaptor semuanya memuat CURLOPT_TIMEOUT (30/60s) + CURLOPT_CONNECTTIMEOUT (10s)

### Cakupan Pengujian

- CircuitBreakerTest 8 kasus + GuardedAdapterTest 13 kasus

### Keterbatasan Diketahui

- State in-memory satu node; deployment multi-node memerlukan shared state Redis
