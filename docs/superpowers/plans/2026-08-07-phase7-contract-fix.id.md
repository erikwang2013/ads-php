# Phase 7: Perbaikan Kontrak Lintas-Platform Implementation Plan

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Pembaruan Status (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ semuanya selesai, verifikasi regresi tester lulus (35 tests OK, pemeriksaan silang kontrak tanpa endpoint hantu, Phase 7 dapat diterima).

**Goal:** Memperbaiki masalah kontrak API lintas-platform yang ditemukan audit tim: 3 endpoint hantu Flutter (404), bug prefiks ganda `admin.ts` Admin, `/system/info` tanpa rute, ServiceProxy tidak terhubung, dokumentasi ketinggalan zaman. Memulihkan konsumsi API service yang konsisten dari tiga platform (Admin/Flutter/HarmonyOS).

**Sumber:** Audit paralel tim 2026-08-07 (backend-dev inventaris 61 rute, vue-dev inventaris 50 titik panggilan Admin, mobile-dev inventaris sisi seluler, perbandingan silang researcher terimplementasi/terencana)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Perbaiki Endpoint Hantu Flutter (🔴 Prioritas Tertinggi)

### Latar Belakang
3 halaman Flutter memanggil rute yang tidak ada di service, semuanya 404:

| Panggilan Flutter | Rute aktual service | Solusi Perbaikan |
|---|---|---|
| `GET /dashboard` | Tidak ada (ringkasan dasbor ada di `/reports/summary`) | Ubah ke `GET /reports/summary` |
| `GET /alerts` | Tidak ada (peringatan ada di `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | Ubah ke `GET /alerts/logs` (semantik daftar peringatan) |
| `GET /reports` | Tidak ada (laporan ada di `/reports/summary`, `/reports/custom`) | Ubah ke `GET /reports/custom` (dengan parameter tanggal/dimensi/metrik, cocok dengan ReportBuilder::buildCustom) |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 rentang, sesuaikan struktur respons `data.overview`/`by_platform`/`daily`) ✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, sesuaikan struktur paginasi `data.list`, field AlertLog rule_name/metric/current_value/condition/threshold) ✅
- Modify: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, parameter date_start/date_end/dimensions[]/metrics[], parse `data.list`, field cost) ✅
- Verify: Field respons sesuai dengan yang sebenarnya dikembalikan `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### Kriteria Penerimaan
- [x] Tiga modifikasi path selesai, parameter query dipertahankan (parameter tanggal halaman report → date_start/date_end + dimensions/metrics) ✅
- [x] Parsing respons selaras dengan struktur JSON aktual backend (overview / paginated list / custom list) ✅
- [x] `flutter analyze` tanpa error setelah modifikasi — cache SDK Flutter lingkungan ini read-only tidak dapat dijalankan, diganti `dart analyze` bawaan SDK seluruh proyek **0 errors** (15 warning yang ada semuanya sudah ada sebelum perubahan, tidak ada masalah baru yang diperkenalkan) ✅

---

## Task 2: Perbaiki Bug Prefiks Ganda `admin.ts` Admin

### Latar Belakang
- Path `admin/public/web/src/api/admin.ts` ditulis `/api/admin/...`, sedangkan axios baseURL sudah `/api` (`src/api/index.ts`), hasilnya tergabung menjadi `/api/api/admin/...`, 5 panggilan UserManage.vue / AuditLog.vue kemungkinan besar 404.
- **Masalah arsitektur mendalam (dikonfirmasi laporan akhir vue-dev)**: backend admin (8789) sendiri menyediakan 12 rute lokal (`/api/admin/login`, `me`, `logout`, `users` CRUD, `roles`, `audit-logs`, `/api/install/*`), tetapi:
  - `location /api/` di `docker/nginx/admin.conf` **semuanya** di-proxy_pass ke `service_api` (php:8788);
  - `upstream admin_backend` (admin-php:8789) sudah didefinisikan, tetapi **tidak ada location yang merujuknya** → di lingkungan produksi `/api/admin/*` tidak akan pernah sampai ke 8789;
  - Proxy dev Vite juga mengarahkan semua `/api` ke 8788.
  - Kesimpulan: meskipun prefiks ganda diperbaiki, `/api/admin/*` tetap 404 — rute lokal backend admin tidak terhubung di jalur produksi.

### Titik Keputusan (perlu konfirmasi backend-dev + vue-dev + devops)
- Opsi A (direkomendasikan): vue-dev mengubah path `admin.ts` menjadi relatif `/admin/users`, `/admin/audit-logs`, sekaligus **devops menambahkan `location /api/admin/` → `proxy_pass http://admin_backend` di Nginx** (diletakkan sebelum `location /api/`, pencocokan prefiks presisi lebih diutamakan), sehingga rute khusus admin dilayani langsung oleh 8789, rute bisnis tetap melalui 8788
- Opsi B: backend-dev menambahkan rute `/api/admin/*` di service (tumpang tindih tanggung jawab dengan sisi Admin, tidak direkomendasikan)
- Opsi C: query bisnis juga diubah melalui ServiceProxy (perlu penyambungan, perubahan terbesar, hanya dipertimbangkan jika admin memerlukan autentikasi terpadu)

### Files:
- Modify: `admin/public/web/src/api/admin.ts` (hilangkan prefiks `/api`)
- Modify: `docker/nginx/admin.conf` (tambah `location /api/admin/` → upstream admin_backend)
- Modify: `admin/public/web/vite.config.ts` (proxy dev tambah aturan `/api/admin` → 8789, diletakkan sebelum `/api`)
- Verify: rute backend admin di `admin/config/route.php` (/api/admin/users dll.) cocok dengan panggilan frontend

### Kriteria Penerimaan
- [x] Path permintaan frontend sesuai dengan rute backend yang benar-benar ada (tanpa 404) — 9 metode admin.ts semua diperiksa dengan route.php ✅, vue-tsc lulus
- [x] Nginx / Vite keduanya membagi `/api/admin/*` ke 8789 dengan benar, sisanya `/api/*` ke 8788 — Nginx tambah `location /api/admin/`, Vite tambah proxy `/api/admin` (diletakkan sebelum `/api`) ✅
- [x] Fungsi halaman UserManage / AuditLog dapat digunakan — path sudah diselaraskan (termasuk keputusan listRoles → `/admin/users/roles`) ✅

---

## Task 3: `/system/info` Tanpa Rute + Keputusan ServiceProxy

### Latar Belakang
- `SystemInfo.vue` / `stores/admin.ts` memanggil `GET /api/system/info`, service tidak memiliki rute ini (hanya /health, /ping), 404 ditelan try/catch
- `admin/app/controller/ServiceProxy.php` sudah didefinisikan tetapi 0 pemanggil aktif di seluruh repo ("sudah didefinisikan belum terhubung")

### Titik Keputusan
- `/system/info`: Opsi A — frontend diubah memanggil `/health` (service sudah punya); Opsi B — backend-dev menambahkan endpoint `/api/system/info` di service (mengembalikan informasi versi/lingkungan, berguna juga untuk HarmonyOS/Flutter, direkomendasikan)
- ServiceProxy: Opsi A — hubungkan ke API khusus admin yang dibutuhkan admin (mis. penerusan log audit); Opsi B — hapus kelas dan perbarui dokumentasi nyatakan "Admin terhubung langsung ke service" (arsitektur aktual saat ini)

### Sudah Dieksekusi (2026-08-16)
- **`/system/info` → Opsi A (frontend diubah memanggil `/health`)** : SystemInfo.vue diubah ke axios native panggil `GET /health`, tentukan `checks.database === 'ok'`; rute `/health` di sisi service tanpa prefiks `/api`, Vite sudah menambah proxy `/health`, `location /health` di Nginx sudah ada; kode mati `stores/admin.ts` ikut diubah ke `/health` ✅
- **ServiceProxy → Opsi B (dipertahankan + penjelasan dokumen)** : kelas dipertahankan sebagai infrastruktur cadangan (`ServiceProxy::init()` inisialisasi diri tidak berbahaya), komentar `admin/config/app.php` diperbarui menjadi "infrastruktur cadangan, saat ini tanpa pemanggil aktif" ✅

### Kriteria Penerimaan
- [x] Keputusan `/system/info` terlaksana: frontend sudah menghapus panggilan (diubah ke /health), tanpa permintaan hantu 404 ✅
- [x] Keputusan ServiceProxy terlaksana: kelas dipertahankan dan status dijelaskan di komentar config ✅

---

## Task 4: Pengisian Ulang Dokumen & Penyeragaman Istilah

### Latar Belakang
- README "14 controller / 45+ endpoint" ketinggalan zaman (aktual 17 controller / 61 endpoint)
- Checkbox phase di `docs/superpowers/plans/` belum diisi ulang (kode sudah diimplementasikan tetapi dokumen belum dicentang)
- Status HarmonyOS "UI sedang direncanakan" ketinggalan zaman (aktual 6 halaman + ApiClient sudah siap)
- Default `.../api/v1` di install.html / InstallController tidak konsisten dengan default config `/api` (header X-API-Version)
- Komentar CacheService menyebut cache dua tingkat, padahal tiga tingkat (L1 memori / APCu / Redis)

### Files:
- Modify: `README.md` / `README.en.md` (jumlah controller, jumlah endpoint, status HarmonyOS, tingkat cache)
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php` (penyeragaman prefiks versi)
- Modify: `service/support/CacheService.php` (koreksi komentar)
- Optional: isi ulang checkbox `docs/superpowers/plans/*.md`

### Sudah Dieksekusi (2026-08-16)
- README.md / README.en.md: 17 controller / 61 endpoint / 6 halaman HarmonyOS / 19 halaman Vue / istilah koneksi langsung SPA semuanya diperbarui ✅
- install.html / InstallController: nilai default `/api/v1` → `/api` (mekanisme header X-API-Version) ✅
- Checkbox 8 phase plan semuanya diisi ulang ✅ (kecuali phase7, menunggu dieksekusi)

### Kriteria Penerimaan
- [x] Data README konsisten dengan kode (17 controller / 61 endpoint / 6 halaman HarmonyOS) ✅
- [x] Prefiks versi wizard instalasi konsisten dengan mekanisme X-API-Version ✅

---

## Perencanaan Fase Berikutnya (Phase 8-10, di luar rencana ini)

| Phase | Isi | Status |
|---|---|---|
| Phase 8 | Realisasi saluran peringatan: ads-alert tambah channel/ (Email SMTP, Webhook, placeholder gateway SMS) — menutup celah sisa Phase 5 | Menunggu dimulai |
| Phase 9 | Realisasi integrasi HarmonyOS: 6 halaman terhubung ApiClient (saat ini 0 panggilan nyata, semua data simulasi) | Menunggu dimulai |
| Phase 10 | Pendalaman & komersialisasi: integrasi nyata 29 platform, visualisasi status sinkronisasi, loop tertutup data konversi, CI packaging Flutter/HarmonyOS, kuota SaaS multi-tenant | Menunggu dimulai |
