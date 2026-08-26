# Phase 8: Realisasi Saluran Peringatan Multi-Saluran Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Goal:** Menutup celah sisa Phase 5 — saluran email/sms `NotificationService` ditingkatkan dari stub echo menjadi implementasi nyata (email SMTP + Webhook umum), dan mendukung konfigurasi saluran. Saluran web dan Redis pub/sub sudah diimplementasikan, tetap tidak berubah.

**Sumber:** Kesimpulan audit tim Phase 7 (perbandingan perencanaan researcher: satu-satunya item "selesai sebagian" yang jelas = saluran peringatan multi Phase 5, `ads-alert` kekurangan direktori `channel/`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## Status Saat Ini (Telah Diverifikasi)

| Komponen | Status |
|---|---|
| `NotificationService::send()` | `match ($channel)` mendistribusikan web/email/sms; web menulis nyata ke `erik_notifications`, email/sms adalah stub echo |
| `AlertRule.channels` | Field JSON + Eloquent cast array, frontend sudah mengirim `['web','email','sms']` |
| Admin AlertRuleList.vue | Sudah ada UI ceklis saluran (web terkunci, email/sms opsional) |
| Redis pub/sub | Penerusan saluran `alert:new` sudah diimplementasikan |
| Konfigurasi SMTP/email | Tidak ada (service/config tidak ada konfigurasi mail) |

## Task 1: Saluran Email (SMTP)

### Files:
- Create: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, digerakkan env)
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php` (implementasi send(AlertLog, AlertRule))
- Modify: `service/plugin/ads-alert/service/NotificationService.php` (cabang email memanggil EmailChannel, hapus stub echo)
- Modify: `service/composer.json` (jika memilih PHPMailer perlu tambah dependensi; prioritaskan implementasi `mail()`/socket tanpa dependensi agar tetap ringan, evaluasi oleh implementer)

### Poin Desain
- Penerima: dibaca dari konfigurasi AlertRule atau konfigurasi tenant (jika tidak ada, gunakan field `email` atau default konfigurasi)
- Subjek/isi: gunakan kembali template teks sendWeb ("告警触发: {rule.name}" + metrik/nilai saat ini/kondisi/ambang)
- Penanganan kegagalan: tangkap exception catat log, tidak memengaruhi saluran lain dan alur utama
- Degradasi elegan saat konfigurasi tidak ada (log peringatan, tidak melempar exception menghentikan)

## Task 2: Saluran Webhook

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON ke URL yang dikonfigurasi)
- Modify: `match` di `NotificationService::send()` tambah cabang `'webhook'`

### Poin Desain
- Sumber konfigurasi: AlertRule perluas field `webhook_url` (migration) atau konfigurasi channels; untuk perubahan minimal, prioritaskan tambah kolom `webhook_url` di AlertRule (nullable)
- Payload: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, berisi tingkat peringatan/metrik/nilai/ambang/waktu
- Timeout dan retry: timeout koneksi 5 detik, timeout total 10 detik, kegagalan catat log (tidak retry, tetap sederhana)
- Keamanan: hanya izinkan http/https, tidak memvalidasi alamat intranet (risiko SSRF dicatat sebagai batasan yang diketahui, atau validasi bukan intranet — dievaluasi dan dicatat oleh implementer)

## Task 3: Saluran SMS (Placeholder Gateway)

### Files:
- Modify: `NotificationService::sendSms` (pertahankan placeholder, komentar jelas titik integrasi; jika implementer menilai ada solusi ringan dapat direalisasikan)

### Poin Desain
- Gateway SMS (Aliyun/Tencent Cloud) memerlukan AK/SK dan berbayar, tahap ini pertahankan implementasi placeholder, komentar tandai langkah integrasi
- Opsi sms di UI frontend tetap dapat dipilih tetapi backend hanya mencatat log (jelaskan kepada pengguna gateway belum dikonfigurasi)

## Task 4: Konfigurasi Saluran & Frontend

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue` (jika tambah opsi webhook dan input URL)
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php` (pembuatan/pembaruan aturan menerima webhook_url)
- Modify: `service/plugin/ads-alert/model/AlertRule.php` (fillable/casts tambah webhook_url)
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER atau jelaskan skrip inkremental)

### Kriteria Penerimaan
- [ ] Saluran email: setelah konfigurasi SMTP, pemicu peringatan dapat menerima email; saat tidak dikonfigurasi degradasi elegan
- [ ] Saluran webhook: saat pemicu peringatan POST JSON ke URL yang dikonfigurasi, field payload lengkap
- [ ] Saluran sms: tetap placeholder, catat log
- [ ] Regresi saluran web dan Redis pub/sub tidak terpengaruh
- [ ] Formulir aturan Admin dapat mengonfigurasi field saluran baru
- [ ] `php vendor/bin/phpunit --no-coverage` semua lulus
- [ ] Tes baru/diperbarui: tes distribusi saluran AlertEngine/NotificationService
