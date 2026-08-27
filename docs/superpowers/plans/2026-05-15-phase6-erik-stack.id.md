# Phase 6: Refactoring Arsitektur Erik Stack

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Refactoring menyeluruh: prefiks database, sistem ID, sistem enkripsi, hak cipta, standar kode

## Daftar Perubahan

| # | Perubahan | Paket | Cakupan Pengaruh |
|---|-----------|-------|------------------|
| 1 | Prefiks tabel database `ads_` | — | Semua file SQL/migrasi |
| 2 | Kunci utama Snowflake ID (tanpa auto-increment) | erikwang2013/snowflake-php | Semua Model + SQL |
| 3 | Enkripsi/dekripsi hashids ID API | erikwang2013/hashids | Semua respons Controller |
| 4 | Beralih autentikasi JWT | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | Enkripsi/dekripsi data sensitif API | erikwang2013/encryption | Lapisan permintaan/respons API |
| 6 | Enkripsi/dekripsi data sensitif DB | erikwang2013/encryptable | Lapisan Eloquent Model |
| 7 | Sinkronisasi/kueri data ES | erikwang2013/webman-scout | Pencarian laporan |
| 8 | Bendera negara | erikwang2013/season | Label platform frontend |
| 9 | Pernyataan hak cipta | — | Header semua file |
| 10 | Hapus prefiks global `\` | — | Semua file PHP |
| 11 | Tambah komentar di file konfigurasi | — | config/*.php |
| 12 | Tata letak Flutter Web PC | — | Proyek Flutter |
| 13 | Peningkatan visual panel Admin | — | Grafik dasbor |
| 14 | Ekspor data panel ke PDF | — | Format ekspor baru |
| 15 | Ekspor Excel (Client+Admin) | — | Peningkatan ekspor |
| 16 | Aplikasi HarmonyOS | — | Proyek HarmonyOS baru |

## Urutan Implementasi

**Batch A: Infrastruktur (dependensi + ID + enkripsi)**
- Perbarui composer.json tambahkan 6 paket erikwang2013
- Tulis ulang semua file migrasi SQL (prefiks ads_ + bigint tanpa auto-increment)
- Buat trait Snowflake ID
- Perbarui semua Model (gunakan SnowflakeTrait)
- Konfigurasi middleware hashids
- Beralih JWT ke jwt-webman

**Batch B: Pembersihan Kode**
- Hapus semua prefiks global `\`
- Tambahkan header hak cipta ke semua file
- Tambahkan komentar ke file konfigurasi

**Batch C: Peningkatan Frontend**
- Peningkatan visual panel Admin (lebih banyak grafik, data real-time)
- Ekspor data panel ke PDF
- Peningkatan ekspor Excel

**Batch D: Flutter + HarmonyOS**
- Proyek tata letak Flutter Web PC
- Kerangka proyek HarmonyOS
