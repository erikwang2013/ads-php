# Phase 10: Pendalaman & Komersialisasi Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** Di atas fondasi kontrak dan multi-saluran Phase 7-9, wujudkan empat kemampuan pendalaman: visualisasi status sinkronisasi, loop tertutup data konversi, CI packaging seluler, kuota SaaS multi-tenant.

**Sumber:** Arah yang diinferensikan audit tim Phase 7 (researcher: realisasi ES/pemisahan baca-tulis/antrian, CI Flutter/HarmonyOS, integrasi nyata 29 platform, kuota penagihan SaaS, loop tertutup data konversi, visualisasi status sinkronisasi, saran bidding AI)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## Status Saat Ini (Telah Diverifikasi)

| Sub-item Kandidat | Status |
|---|---|
| Visualisasi status sinkronisasi | Tabel `erik_sync_errors` + `RetrySyncTask` (retry 3 kali, backoff 5^n menit) sudah ada; **tidak ada halaman frontend/API yang menampilkan tingkat kegagalan sinkronisasi dan latensi** |
| Loop tertutup data konversi | Tabel `erik_conversions` + `erik_attribution_results` sudah ada, mesin atribusi sudah diimplementasikan; **tidak ada pintu masuk pengumpulan data konversi** (API callback/tracking) |
| CI seluler | `ci.yml` hanya sintaks PHP→PHPUnit→vue-tsc→Docker; **tidak ada build/packaging Flutter/HarmonyOS** |
| SaaS multi-tenant | Tabel `erik_tenants` + middleware TenantIdentify sudah ada; **tidak ada penagihan/kuota/statistik penggunaan** |
| Realisasi ES | scout.php sudah dikonfigurasi + dependensi webman-scout sudah diperkenalkan; **docker-compose tidak memiliki layanan ES** |
| Integrasi nyata 29 platform | Kode 29 adapter lengkap; **tidak ada catatan integrasi sandbox/kredensial** (perlu kredensial eksternal, ditandai sebagai item manual) |

## Task 1: Visualisasi Status Sinkronisasi

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` atau tambah `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue` (atau gabung ke halaman sistem)

### Poin Desain
- Endpoint: `GET /api/sync/status` (dimensi akun: last_sync_at, tingkat keberhasilan, jumlah gagal hari ini, jumlah retry pending) + `GET /api/sync/errors` (daftar error terpaginasi, berisi last_error/retry_count/next_retry_at)
- Frontend: halaman status sinkronisasi (tabel + kartu ringkasan), hanya jalur versi Full/Standard
- Sumber data: erik_platform_accounts (last_sync_at) + erik_sync_errors

## Task 2: API Pengumpulan Data Konversi

### Files:
- Modify: `service/plugin/ads-api/controller/v1/` (tambah ConversionController + route)
- Create: `service/plugin/ads-report/service/ConversionService.php`

### Poin Desain
- Endpoint: `POST /api/conversions` (callback konversi dari pihak bisnis: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (query)
- Validasi: campaign_id ada, jumlah non-negatif, format waktu; tulis ke erik_conversions
- Kaitan atribusi: setelah callback dapat memicu perhitungan ulang atribusi (atau jelaskan dihitung ulang oleh AttributionEngine yang ada secara terjadwal/manual)
- Frontend: halaman laporan atribusi tambah penjelasan/demo "callback konversi" (opsional)

## Task 3: CI Packaging Seluler

### Files:
- Modify: `.github/workflows/ci.yml` (tambah job: Flutter build (web + linux atau apk) + pemeriksaan statis HarmonyOS)

### Poin Desain
- Flutter: `flutter pub get && flutter analyze && flutter build web` (atau apk, pilih target yang dapat dibuild sesuai status repo; jika lingkungan flutter terbatas gunakan dart analyze)
- HarmonyOS: tidak ada toolchain CI Linux standar, lakukan penjelasan pemeriksaan statis atau lewati (ditandai)
- Paralel dengan job php-tests yang ada, tidak memblokir alur utama

## Task 4: Kuota SaaS Multi-Tenant (MVP)

### Files:
- Modify: `service/plugin/ads-tenant/` (tambah QuotaService)
- Modify: `service/plugin/ads-api/config/route.php` + controller

### Poin Desain
- Data: erik_tenants tambah field quota atau tabel baru erik_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- Titik validasi: jumlah pengikatan akun, jumlah pembuatan kampanye, jumlah sinkronisasi harian (periksa di pintu masuk AccountController/CampaignController/DataSyncTask)
- Endpoint: `GET /api/tenant/quota` (penggunaan + kuota)
- Frontend: halaman sistem tampilkan penggunaan kuota (opsional, MVP bisa hanya API)
- Jalur versi: nilai default quota dibedakan per lite/standard/full (konstanta config)

## Kriteria Penerimaan (per Task)
- [ ] Task 1: endpoint API sync dapat digunakan, halaman frontend menampilkan, cakupan tes
- [ ] Task 2: API callback conversions dapat ditulis dan dibaca, validasi berlaku, cakupan tes
- [ ] Task 3: job baru CI lulus (atau tandai item yang dilewati dengan jelas)
- [ ] Task 4: API quota mengembalikan benar, pemblokiran melebihi batas berlaku, cakupan tes
- [ ] Semua: `php vendor/bin/phpunit --no-coverage` semua lulus, vue-tsc lulus

## Tidak Termasuk Periode Ini (Perlu Sumber Daya Eksternal)
- Integrasi nyata 29 platform (perlu kredensial/sandbox masing-masing platform)
- Realisasi layanan ES (perlu menambah layanan ES dan inisialisasi indeks di docker-compose)
- Saran bidding AI (persiapan model/data)
