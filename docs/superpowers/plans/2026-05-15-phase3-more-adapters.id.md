# Phase 3: Ekspansi Adapter Platform Iklan Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Tujuan:** Menambahkan adapter untuk empat platform: Tencent Ads, Umeng, Kuaishou Magi Engine (磁力引擎), dan Xiaohongshu Pugongying (蒲公英).

**Adapter yang sudah ada (Phase 1+2):** Juliang (巨量引擎), Baidu Marketing, Taobao/Alimama

**Arsitektur:** Setiap adapter mengimplementasikan antarmuka `PlatformAdapter` dan didaftarkan ke `AdapterRegistry`, sehingga dapat dipanggil secara terpadu oleh alur otorisasi OAuth, tugas sinkronisasi data, dan admin frontend.

---

## Task 13: Membuat Adapter Tencent Ads

**File:**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spesifikasi Adapter

API Tencent Ads (Guangdiantong):
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- Cara autentikasi: parameter URL `access_token` + `nonce`/`timestamp` anti-replay
- Kampanye iklan: `campaigns/get` + `campaigns/add` + `campaigns/update`
- Laporan: `daily_reports/get` (async: buat tugas → polling → ambil)
- Satuan mata uang: sen (konsisten dengan model terpadu, tanpa konversi)
- Pemetaan status: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Tanda Tangan API Khas Tencent

Tencent menggunakan `access_token` sebagai parameter URL, tidak memerlukan tanda tangan MD5, tetapi membutuhkan `nonce` (angka acak) + `timestamp` anti-replay.

```php
protected function request(string $method, string $path, array $params, string $accessToken): array
{
    $url = $this->baseUrl . ltrim($path, '/');
    $params['access_token'] = $accessToken;
    $params['nonce'] = bin2hex(random_bytes(8));
    $params['timestamp'] = time();

    $ch = curl_init();
    if ($method === 'GET') {
        $url .= '?' . http_build_query($params);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new \RuntimeException('Tencent API network error: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($body, true);
    if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
        throw new \RuntimeException(
            'Tencent API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
        );
    }
    return $decoded;
}
```

**Poin utama pemetaan field:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (satuan sudah sen, tanpa konversi)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- Di laporan `cost` (sen)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: Membuat Adapter Umeng

**File:**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spesifikasi Adapter

Umeng (U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- Cara autentikasi: API Key + API Secret + tanda tangan MD5
- Umeng berfokus pada **pemantauan efektivitas promosi**, berbeda dengan platform penayangan iklan — Umeng tidak membuat/mengelola kampanye iklan secara langsung, melainkan melacak data promosi dari berbagai kanal
- capabilities: `['report', 'oauth']` (tidak mendukung campaign/create/update/toggle)
- Antarmuka laporan: `/v1/ad_analytics/report` mengembalikan data promosi per dimensi kanal/tanggal
- fetchCampaigns mengembalikan kosong (Umeng tidak membuat kampanye sendiri)
- fetchReports menarik data efektivitas promosi dan memetakannya ke model laporan terpadu

### Algoritma Tanda Tangan Umeng

```
sign = md5(method + url + body + api_secret)
```

Autentikasi dikirim melalui HTTP Header: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Poin utama pemetaan field:**
- `channel` → `platform_campaign_id` (identitas kanal dipetakan ke dimensi kampanye)
- `pv` → `impressions` (tayangan)
- `click` → `clicks` (klik)
- `activation` → `conversions` (aktivasi/konversi)
- Satuan `cost`: yuan → sen (×100)

---

## Task 15: Membuat Adapter Kuaishou Magi Engine

**File:**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spesifikasi Adapter

Kuaishou Magi Engine (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- Cara autentikasi: Header `access_token`
- Kampanye iklan: `/campaign/list` + `/campaign/create` + `/campaign/update`
- Laporan: `/report/campaign/report` (dikembalikan sinkron)
- Satuan mata uang: yuan → sen (×100)

**Poin utama pemetaan field:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (yuan→sen ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- Di laporan `charge`→`cost` (yuan→sen)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: Membuat Adapter Xiaohongshu Pugongying

**File:**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spesifikasi Adapter

Xiaohongshu Pugongying (platform Juguang Xiaohongshu):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- Cara autentikasi: Header `access_token` (`Authorization: Bearer xxx`)
- Kampanye iklan: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Laporan: `/v1/report/campaign/report`
- Satuan mata uang: sen (API Xiaohongshu mengembalikan sen, tanpa konversi)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Poin utama pemetaan field:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (satuan: sen)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- Di laporan `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Kriteria Penerimaan

1. ✅ Adapter Tencent Ads mengimplementasikan seluruh 13 metode PlatformAdapter
2. ✅ Adapter Umeng mengimplementasikan kemampuan report + oauth (Umeng tidak mendukung operasi penayangan)
3. ✅ Adapter Kuaishou Magi Engine mengimplementasikan seluruh 13 metode
4. ✅ Adapter Xiaohongshu Pugongying mengimplementasikan seluruh 13 metode
5. ✅ Keempat adapter didaftarkan di bootstrap.php
6. ✅ `GET /api/v1/platforms` mengembalikan 7 platform (termasuk 3 sebelumnya)
7. ✅ Semua panggilan curl adapter menangani error dengan benar (curl_errno + CURLOPT_CONNECTTIMEOUT)
