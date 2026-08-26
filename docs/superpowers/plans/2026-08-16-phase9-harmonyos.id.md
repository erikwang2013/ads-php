# Phase 9: Realisasi Integrasi Nyata HarmonyOS Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** Mengalihkan 6 halaman sisi HarmonyOS dari data simulasi menjadi panggilan API nyata (service :8788), memperbaiki masalah baseUrl hardcoded ApiClient, mewujudkan login nyata, menjadikan sisi HarmonyOS klien ketiga yang dapat digunakan.

**Sumber:** Audit tim Phase 7 (inventaris mobile-dev: 6 halaman HarmonyOS semuanya data simulasi, 0 panggilan nyata, baseUrl ApiClient hardcoded `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## Status Saat Ini (Telah Diverifikasi)

| Komponen | Status |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login sudah lengkap; baseUrl hardcoded `http://127.0.0.1:8788/api` (Flutter menggunakan `/api` relatif same-origin); login() tanpa pemanggil |
| `pages/LoginPage.ets` | Login simulasi (setTimeout 1 detik lalu lompat), komentar "replace with actual API call" |
| `pages/DashboardPage.ets` | Metrik hardcoded `@State` (totalCost=1250000 dll.) |
| `pages/CampaignListPage.ets` | Komentar placeholder L187 `/campaigns` |
| `pages/AccountPage.ets` | Komentar placeholder L138 `/accounts` |
| `pages/AlertPage.ets` | Komentar placeholder L146 `/alerts` |
| `pages/ReportPage.ets` | Komentar placeholder L242 `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric sudah ada |
| i18n | StringResources.ets (15+ keys) |

## Task 1: Peningkatan ApiClient

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Poin Desain
- **baseUrl diubah menjadi dapat dikonfigurasi** : pertahankan setBaseUrl, nilai default tetap `http://127.0.0.1:8788/api` (perangkat nyata/emulator perlu menunjuk ke alamat LAN, jelaskan dengan komentar); hindari path relatif same-origin gaya Flutter (ArkTS harus URL absolut)
- **Perbaiki bug replayHeaders duplikat** : `{ ...this.replayHeaders(), ...this.replayHeaders() }` penyebaran duplikat (di dalam metode get) → sekali
- **Adaptasi nilai kembali login()** : service `POST /api/auth/login` mengembalikan `{access_token, token_type, expires_in, user}` (periksa field aktual `service/plugin/ads-api/controller/v1/AuthController.php` — adalah access_token bukan token, perlu diverifikasi lalu koreksi pengecekan `data.token`)
- **Penanganan error** : saat resp.responseCode bukan 2xx lempar error/kembalikan pesan error yang jelas; proteksi kegagalan JSON.parse
- Pertahankan konvensi yang ada get/post/put/delete mengembalikan `data.data` (pembungkusan ApiResponse)

## Task 2: Login Nyata LoginPage

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Poin Desain
- `handleLogin()` memanggil `ApiClient.login(username, password)`; berhasil → setToken + lompat ke Dashboard; gagal → toast pesan error
- Status loading isLoading sudah ada, gunakan kembali
- Pesan error prioritaskan message yang dikembalikan service (envelope ApiResponse), jika tidak ada gunakan teks umum

## Task 3: Realisasi Lima Halaman Bisnis

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### Perbandingan Endpoint (dikonfirmasi audit Phase 7, konsisten dengan perbaikan Flutter)
| Halaman | Panggilan | Parsing |
|---|---|---|
| DashboardPage | `GET /reports/summary` (interval hari ini) | `data.overview` → totalCost/total_impressions/avg_ctr dll. (jumlah dalam sen, formatFen sudah ada) |
| CampaignListPage | `GET /campaigns` | `data.list` (terpaginasi) → model Campaign |
| AccountPage | `GET /accounts` | `data.list` → model PlatformAccount |
| AlertPage | `GET /alerts/logs` | `data.list` → field AlertLog (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### Poin Desain
- Muat halaman (aboutToAppear) memicu permintaan; inisialisasi data `@State` kosong/0, hindari sisa nilai simulasi
- Gagal muat tampilkan error + retry (rujuk pola error/retry halaman Flutter)
- Satuan jumlah: service mengembalikan angka dalam sen, formatFen sudah menangani
- **Tidak menambah file** , pertahankan struktur UI dan i18n halaman yang ada

## Task 4: Verifikasi

### Kriteria Penerimaan
- [ ] ApiClient tanpa replayHeaders duplikat, field kembalian login konsisten dengan AuthController
- [ ] 6 halaman tanpa sisa data bisnis simulasi hardcoded (verifikasi grep)
- [ ] Path panggilan 5 halaman bisnis berkorespondensi satu-satu dengan rute service (periksa `service/plugin/ads-api/config/route.php`)
- [ ] Pemeriksaan sintaks ArkTS (jalankan jika lingkungan ini punya toolchain hvigor/DevEco; jika tidak jelaskan dan verifikasi manual)
- [ ] Regresi: PHPUnit service tidak terpengaruh
