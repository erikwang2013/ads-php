# Laporan Audit Keamanan dan Perbaikan Ads-PHP (Putaran ke-3)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Waktu pembuatan**: 2026-08-04  
**Cakupan audit**: seluruh middleware keamanan, proses autentikasi, controller instalasi, file konfigurasi  
**Versi PHP**: 8.3.7 | **Framework**: webman v2

---

## I. Ringkasan Perbaikan

Putaran ini melakukan perbaikan menyeluruh terhadap 6 masalah yang ditemukan pada audit keamanan putaran ke-2.

| # | Masalah | Cara perbaikan | Status |
|---|------|---------|:--:|
| 1 | Sisi admin kekurangan 5 middleware keamanan | Buat CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Diperbaiki |
| 2 | AuthCheck admin tidak melakukan binding IP/UA | AuthController menambahkan `_ip` / `_ua` ke payload JWT, AuthCheck memvalidasi binding | Diperbaiki |
| 3 | Risiko ReDoS AttackGuardMiddleware | Tambah pemeriksaan awal `maxStrLen=8192`, string terlalu panjang langsung ditolak | Diperbaiki |
| 4 | Karakter khusus password InstallController | Tambah metode `envQuote()`, otomatis bungkus kutip + escape | Diperbaiki |
| 5 | Konfigurasi middleware admin tidak lengkap | Diperbarui menjadi tumpukan 10 middleware global | Diperbaiki |
| 6 | Jumlah lapisan middleware README ketinggalan zaman | README bahasa Cina dan Inggris disinkronkan | Diperbaiki |

---

## II. Verifikasi Sintaks

| File | Baris | Sintaks |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Lolos |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Lolos |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Lolos |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Lolos |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Lolos |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | Lolos |
| `admin/app/middleware/AuthCheck.php` | 48 | Lolos |
| `admin/app/controller/AuthController.php` | 194 | Lolos |
| `admin/app/controller/InstallController.php` | 298 | Lolos |
| `admin/config/middleware.php` | 43 | Lolos |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | Lolos |

---

## III. Tumpukan Middleware (setelah perbaikan)

### Sisi Service (14 lapisan global + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Sisi Admin (10 lapisan global + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### Matriks Rute (setelah pembaruan sisi admin)

| Rute | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (proteksi) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## IV. Detail Peningkatan Keamanan

### 4.1 Middleware Baru di Admin

| Middleware | File | Tanggung jawab |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | Preflight CORS + header respons, mode debug izinkan semua, produksi whitelist |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Rate limit sliding window Redis 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | Deteksi pola SQL injection (UNION/DROP/ALTER/karakter komentar) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | Input trim + strip_tags (kecuali description/content/extra) |

### 4.2 Binding Token JWT

AuthController menambahkan `_ip` dan `_ua` ke payload JWT saat login:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

Middleware AuthCheck memeriksa konsistensi IP dan UA saat memverifikasi token, menolak akses jika tidak konsisten.

### 4.3 Proteksi ReDoS

AttackGuardMiddleware (admin + service) menambahkan `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 Escape Password .env

InstallController menambahkan metode `envQuote()`, mendeteksi karakter khusus dalam password (spasi, `$`, `#`, kutip, backslash), otomatis membungkus dengan kutip ganda dan melakukan escape.

---

## V. Daftar File

### Baru (5 file)

| File | Baris | Keterangan |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Middleware CORS |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Header respons keamanan |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Rate limit global |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Deteksi SQL injection |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Pembersihan input |

### Dimodifikasi (6 file)

| File | Perubahan |
|------|------|
| `admin/config/middleware.php` | Middleware global 5→10 lapisan |
| `admin/app/middleware/AttackGuardMiddleware.php` | Tambah proteksi ReDoS (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Tambah validasi binding JWT IP/UA |
| `admin/app/controller/AuthController.php` | Payload JWT menambahkan _ip/_ua |
| `admin/app/controller/InstallController.php` | Tambah escape password envQuote() |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Tambah proteksi ReDoS (maxStrLen) |

---

## VI. Kesimpulan

Seluruh 6 masalah keamanan telah diperbaiki. Pertahanan sisi admin meningkat dari 5 lapisan menjadi 10 lapisan middleware global, melengkapi 5 pertahanan kunci: header respons keamanan, rate limit, deteksi SQL injection, pembersihan input, CORS. Token JWT menambahkan verifikasi binding IP/UA. Risiko ReDoS dan masalah karakter khusus password .env telah dihilangkan. Semua file lolos pemeriksaan sintaks PHP.
