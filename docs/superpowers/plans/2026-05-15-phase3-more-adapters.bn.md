# Phase 3: বিজ্ঞাপন প্ল্যাটফর্ম অ্যাডাপ্টার সম্প্রসারণ Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Goal:** নতুন Tencent Ads (腾讯广告), Umeng (友盟), Kuaishou Magnet Engine (快手磁力引擎), Xiaohongshu Pugongying (小红书蒲公英) — চারটি প্ল্যাটফর্মের অ্যাডাপ্টার যোগ করা।

**বিদ্যমান অ্যাডাপ্টার (Phase 1+2):** Ocean Engine (巨量引擎), Baidu Marketing (百度营销), Taobao/Alimama (淘宝/阿里妈妈)

**Architecture:** প্রতিটি অ্যাডাপ্টার `PlatformAdapter` ইন্টারফেস বাস্তবায়ন করে এবং `AdapterRegistry`-তে নিবন্ধিত হয়, ফলে OAuth অনুমোদন প্রক্রিয়া, ডেটা সিঙ্ক টাস্ক এবং ফ্রন্টএন্ড অ্যাডমিন প্যানেল থেকে একীভূতভাবে কল করা যায়।

---

## Task 13: Tencent Ads অ্যাডাপ্টার তৈরি

**ফাইল:**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### অ্যাডাপ্টার স্পেসিফিকেশন

Tencent Ads (Guangdiantong) API:
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- প্রমাণীকরণ: `access_token` URL প্যারামিটার + `nonce`/`timestamp` রিপ্লে-প্রতিরোধ
- বিজ্ঞাপন প্ল্যান: `campaigns/get` + `campaigns/add` + `campaigns/update`
- রিপোর্ট: `daily_reports/get` (অ্যাসিঙ্ক: টাস্ক তৈরি → পোলিং → প্রাপ্তি)
- টাকার একক: ফেন (সেন্ট্রালাইজড মডেলের সাথে সামঞ্জস্যপূর্ণ, কোনো রূপান্তর প্রয়োজন নেই)
- স্ট্যাটাস ম্যাপিং: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Tencent-নির্দিষ্ট API সিগনেচার

Tencent `access_token`-কে URL প্যারামিটার হিসেবে ব্যবহার করে, MD5 সিগনেচার প্রয়োজন নেই, তবে `nonce` (র্যান্ডম সংখ্যা) + `timestamp` রিপ্লে-প্রতিরোধ প্রয়োজন।

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

**ফিল্ড ম্যাপিং পয়েন্ট:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (একক ইতিমধ্যে ফেন, রূপান্তর প্রয়োজন নেই)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- রিপোর্টে `cost` (ফেন)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: Umeng অ্যাডাপ্টার তৈরি

**ফাইল:**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### অ্যাডাপ্টার স্পেসিফিকেশন

Umeng (Umeng U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- প্রমাণীকরণ: API Key + API Secret + MD5 সিগনেচার
- Umeng মূলত **প্রোমোশন ইফেক্ট ট্র্যাকিং**-এ ফোকাস করে, বিজ্ঞাপন ডেলিভারি প্ল্যাটফর্ম থেকে ভিন্ন — এটি সরাসরি বিজ্ঞাপন প্ল্যান তৈরি/ব্যবস্থাপনা করে না, বরং প্রতিটি চ্যানেলের প্রোমোশন ডেটা ট্র্যাক করে
- capabilities: `['report', 'oauth']` (campaign/create/update/toggle সমর্থিত নয়)
- রিপোর্ট API: `/v1/ad_analytics/report` চ্যানেল/তারিখ মাত্রায় প্রোমোশন ডেটা রিটার্ন করে
- fetchCampaigns খালি রিটার্ন করে (Umeng নিজে প্ল্যান তৈরি করে না)
- fetchReports প্রোমোশন ইফেক্ট ডেটা টেনে সেন্ট্রালাইজড রিপোর্ট মডেলে ম্যাপ করে

### Umeng সিগনেচার অ্যালগরিদম

```
sign = md5(method + url + body + api_secret)
```

HTTP Header দিয়ে প্রমাণীকরণ তথ্য পাঠানো হয়: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`।

**ফিল্ড ম্যাপিং পয়েন্ট:**
- `channel` → `platform_campaign_id` (চ্যানেল আইডেন্টিফায়ারকে প্ল্যান মাত্রায় ম্যাপ করা)
- `pv` → `impressions` (ইমপ্রেশন)
- `click` → `clicks` (ক্লিক)
- `activation` → `conversions` (অ্যাক্টিভেশন/কনভার্সন)
- `cost` একক: ইউয়ান → ফেন (×100)

---

## Task 15: Kuaishou Magnet Engine অ্যাডাপ্টার তৈরি

**ফাইল:**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### অ্যাডাপ্টার স্পেসিফিকেশন

Kuaishou Magnet Engine (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- প্রমাণীকরণ: `access_token` Header
- বিজ্ঞাপন প্ল্যান: `/campaign/list` + `/campaign/create` + `/campaign/update`
- রিপোর্ট: `/report/campaign/report` (সিঙ্ক্রোনাস রিটার্ন)
- টাকার একক: ইউয়ান → ফেন (×100)

**ফিল্ড ম্যাপিং পয়েন্ট:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (ইউয়ান→ফেন ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- রিপোর্টে `charge`→`cost` (ইউয়ান→ফেন)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: Xiaohongshu Pugongying অ্যাডাপ্টার তৈরি

**ফাইল:**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### অ্যাডাপ্টার স্পেসিফিকেশন

Xiaohongshu Pugongying (Xiaohongshu Juguang প্ল্যাটফর্ম):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- প্রমাণীকরণ: `access_token` Header (`Authorization: Bearer xxx`)
- বিজ্ঞাপন প্ল্যান: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- রিপোর্ট: `/v1/report/campaign/report`
- টাকার একক: ফেন (Xiaohongshu API ফেন রিটার্ন করে, রূপান্তর প্রয়োজন নেই)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**ফিল্ড ম্যাপিং পয়েন্ট:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (একক: ফেন)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- রিপোর্টে `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## গ্রহণযোগ্যতার মানদণ্ড

1. ✅ Tencent Ads অ্যাডাপ্টার PlatformAdapter-এর 13টি পদ্ধতিই বাস্তবায়ন করে
2. ✅ Umeng অ্যাডাপ্টার report + oauth ক্ষমতা বাস্তবায়ন করে (Umeng ডেলিভারি অপারেশন সাপোর্ট করে না)
3. ✅ Kuaishou Magnet Engine অ্যাডাপ্টার 13টি পদ্ধতিই বাস্তবায়ন করে
4. ✅ Xiaohongshu Pugongying অ্যাডাপ্টার 13টি পদ্ধতিই বাস্তবায়ন করে
5. ✅ 4টি অ্যাডাপ্টারই bootstrap.php-তে নিবন্ধিত
6. ✅ `GET /api/v1/platforms` 7টি প্ল্যাটফর্ম রিটার্ন করে (আগের 3টিসহ)
7. ✅ সব অ্যাডাপ্টারের curl কল সঠিক এরর হ্যান্ডলিং সহ (curl_errno + CURLOPT_CONNECTTIMEOUT)
