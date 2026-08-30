# Panduan Penggunaan

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Untuk instalasi dan deployment lihat bagian « Memulai dengan Cepat » di README; dokumen ini mencakup alur penggunaan lengkap setelah instalasi.

---

## 1. Login Pertama

Setelah instalasi, buka konsol admin:

- Instal satu klik / Docker: `http://localhost`
- Pengembangan lokal: `http://localhost:8789`

Masuk dengan nama pengguna dan kata sandi administrator yang ditetapkan di wizard instalasi. Setelah masuk, Anda tiba di dasbor dengan 8 kartu metrik KPI (total biaya, tayangan, klik, konversi, CTR, CVR, rata-rata CPC, rata-rata CPA), grafik garis tren biaya harian, grafik batang perbandingan platform, dan TOP 10 kampanye.

Untuk mengubah kata sandi atau info akun: Manajemen Sistem → Manajemen Pengguna.

---

## 2. Otorisasi Platform

Sistem mendukung **16 platform domestik + 13 platform internasional**, semuanya diotorisasi melalui « Manajemen Akun → Ikat Akun ».

### Platform OAuth2 (mayoritas)

1. Pilih platform target di halaman « Ikat Akun » dan klik « Otorisasi »
2. Browser dialihkan ke halaman login platform; masuk dan setujui akses
3. Setelah callback, sistem otomatis menyimpan token akses

Platform yang terotorisasi muncul di daftar akun. Token kedaluwarsa otomatis diperbarui oleh `TokenRefreshTask` (pada menit ke-55 setiap jam) — tanpa intervensi manual.

### Platform API Key

Platform seperti Qihoo360, Sogou, dan Umeng menggunakan autentikasi API Key: isi API Key (dan parameter tanda tangan) secara manual di halaman « Ikat Akun », simpan, dan sinkronisasi dimulai.

> 16 platform domestik: Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou, Xiaohongshu, Weibo, Bilibili, Youku Ads, Meituan Ads, Zhihu Ads, Qihoo360, Sogou, Umeng, JD, Pinduoduo Ads
>
> 13 platform internasional: Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. Ikat Akun dan Unggah Perpustakaan Kreatif

### Manajemen Akun

Setelah otorisasi platform, akun muncul di daftar « Manajemen Akun ». Setiap akun dapat mengontrol secara independen apakah ikut sinkronisasi (`sync_enabled`). Hierarki iklan tiga tingkat: Kampanye → Grup Iklan → Kreatif.

### Perpustakaan Kreatif

« Perpustakaan Kreatif » mendukung unggahan gambar/video dengan penjelajahan gaya galeri, untuk digunakan pada kreatif iklan. Aset yang diunggah dapat menggunakan penyimpanan CDN secara opsional (lihat di bawah).

### Konfigurasi Penyedia Penyimpanan CDN

Sistem memiliki abstraksi penyimpanan bawaan dengan beberapa driver; beberapa penyedia dapat dikonfigurasi sekaligus:

| Driver | Deskripsi |
|--------|-----------|
| Penyimpanan Lokal | Driver bawaan, menyimpan di disk server |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| Kompatibel S3 | S3CompatibleStorage (kompatibel dengan AWS S3, Qiniu Cloud, MinIO, dll.) |

Tambahkan penyedia di halaman « Penyedia CDN » dan isi kunci/parameter wilayah yang sesuai untuk mengaktifkannya.

### Unggah Pra-Tanda Tangan dan Bersihkan Cache

- **Unggah pra-tanda tangan**: server menerbitkan URL pra-tanda tangan berbatas waktu (PUT OSS/S3) untuk setiap unggahan; browser atau klien seluler mengunggah langsung ke penyimpanan objek, melewati server aplikasi — lebih hemat bandwidth dan beban
- **Bersihkan cache**: setelah aset diperbarui atau dihapus, pembersihan cache CDN dapat dipicu agar klien selalu mendapat konten terbaru

---

## 4. Sinkronisasi Data

Sinkronisasi digerakkan oleh 6 tugas terjadwal (dijadwalkan dalam proses oleh plugin crontab webman — tanpa crontab eksternal):

| Tugas | Frekuensi | Tanggung jawab |
|-------|-----------|----------------|
| RetrySyncTask | Setiap 3 menit | Ulangi sinkronisasi terakhir yang gagal |
| AlertCheckTask | Setiap 5 menit | Evaluasi aturan peringatan |
| DataSyncTask | Setiap 10 menit | Sinkronkan Kampanye/Grup Iklan/Kreatif dan laporan (2 hari terakhir, 9 metrik) |
| BidCheckTask | Setiap 10 menit | Periksa aturan bid otomatis |
| BudgetCheckTask | Setiap 15 menit | Pemeriksaan peringatan anggaran |
| TokenRefreshTask | Menit ke-55 setiap jam | Perbarui token platform yang kedaluwarsa |

Konfigurasi tugas ada di `service/plugin/ads-task/config/cron.php`; frekuensinya dapat diubah. Status sinkronisasi terlihat di halaman « Sinkronisasi Data »; sakelar per akun ada di « Manajemen Akun ».

---

## 5. Analisis Laporan

### Dasbor

8 kartu metrik KPI + grafik garis tren harian + grafik batang perbandingan platform + TOP 10 kampanye, dengan filter rentang tanggal dan ekspor PDF/Excel sekali klik.

### Laporan Kustom

- **Dimensi**: date, platform, campaign
- **Metrik**: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Mendukung kueri dimensi gabungan dan pengurutan

### Analisis Atribusi

Mesin atribusi lintas platform bawaan mendukung **5 model atribusi**: first_touch, last_touch, linear, time_decay, position_based, dengan jendela lihat-balik 30 hari. Di halaman « Analisis Atribusi », pilih model dan rentang tanggal untuk melihat kontribusi setiap saluran.

### Kalender Kampanye

« Kalender Kampanye » menampilkan jadwal penayangan setiap kampanye dalam tampilan kalender untuk gambaran cepat ritme penayangan harian.

### Ekspor

Laporan mendukung tiga format ekspor:

- **CSV** (UTF-8 BOM, terbuka langsung di Excel tanpa karakter rusak)
- **Excel** (HTML .xls)
- **PDF** (tata letak cetak HTML)

---

## 6. Peringatan dan Notifikasi

### Aturan Peringatan

Buat aturan di halaman « Aturan Peringatan »: pilih objek yang dipantau (anggaran/biaya/tayangan/klik, dll.), ambang batas dan perbandingan, cakupan efektif, serta saluran notifikasi. Aturan yang aktif dievaluasi oleh `AlertCheckTask` setiap 5 menit dan terpicu saat cocok.

### Saluran Notifikasi

| Saluran | Deskripsi |
|---------|-----------|
| Web | Notifikasi dalam aplikasi, terlihat di « Pusat Notifikasi » |
| Email | Dikirim melalui email (SMTP, dengan cadangan `mail()`); atur alamat penerima di aturan peringatan |
| SMS | Dikirim melalui SMS |
| Webhook | POST JSON ke URL callback yang dikonfigurasi; dapat diintegrasikan dengan WeCom/DingTalk/Feishu, dll. |

Riwayat peringatan terlihat di halaman « Log Peringatan ».

---

## 7. Aplikasi Seluler

### Aplikasi Flutter (12 halaman: Login/Dasbor/Akun/Kampanye/Grup Iklan/Kreatif/Laporan/Bid/Peringatan/Notifikasi, dll.)

```bash
cd apps/flutter
flutter run -d chrome     # PC Web
flutter run -d android    # Ponsel Android
```

### Aplikasi HarmonyOS

Buka direktori `apps/harmonyos` dengan DevEco Studio dan jalankan.

---

## 8. Multi-Tenant

Sistem memiliki plugin multi-tenant bawaan (ads-tenant):

- **Identifikasi tenant**: middleware `TenantIdentify` mengidentifikasi tenant saat ini per permintaan
- **Isolasi data**: dua mode — database bersama diisolasi dengan `tenant_id`, atau database terpisah per tenant (`db_type`)
- **Manajemen kuota**: `QuotaService` memvalidasi kuota tenant (jumlah akun, aset, dll.); permintaan melebihi kuota ditolak

---

## Dokumen Terkait

- [Dokumen Fitur](features.id.md) — 21 modul/alur bisnis
- [Referensi API](api.id.md) — semua definisi antarmuka
- [Arsitektur](architecture.id.md) — deployment/keamanan/model data
