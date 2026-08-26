# Phase 3: विज्ञापन प्लेटफ़ॉर्म एडाप्टर विस्तार Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**लक्ष्य:** टेनसेंट विज्ञापन (腾讯广告), उमेंग (友盟), कुआइशौ मैगनेट इंजन (快手磁力引擎), शियाओहोंगशू डंडेलियन (小红书蒲公英) चार प्लेटफ़ॉर्म के एडाप्टर जोड़ें।

**मौजूदा एडाप्टर (Phase 1+2):** 巨量引擎 (Juliang), 百度营销 (Baidu), 淘宝/阿里妈妈 (Taobao/Alimama)

**Architecture:** हर एडाप्टर `PlatformAdapter` इंटरफ़ेस लागू करता है और `AdapterRegistry` में पंजीकृत होता है, जिससे OAuth प्रमाणीकरण फ़्लो, डेटा सिंक कार्य और फ़्रंटएंड प्रबंधन पैनल एकीकृत रूप से कॉल कर सकते हैं।

---

## Task 13: टेनसेंट विज्ञापन एडाप्टर बनाएँ

**फ़ाइलें:**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### एडाप्टर विशिष्टता

टेनसेंट विज्ञापन (广点通) API:
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- प्रमाणीकरण: `access_token` URL पैरामीटर + `nonce`/`timestamp` रीप्ले सुरक्षा
- विज्ञापन प्लान: `campaigns/get` + `campaigns/add` + `campaigns/update`
- रिपोर्ट: `daily_reports/get` (असिंक्रोनस: कार्य बनाएँ→पोल करें→प्राप्त करें)
- मुद्रा इकाई: फ़ेन (分) (एकीकृत मॉडल के अनुरूप, रूपांतरण आवश्यक नहीं)
- स्थिति मैपिंग: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### टेनसेंट-विशिष्ट API सिग्नेचर

टेनसेंट `access_token` को URL पैरामीटर के रूप में उपयोग करता है, MD5 सिग्नेचर की आवश्यकता नहीं, लेकिन रीप्ले सुरक्षा के लिए `nonce` (यादृच्छिक संख्या) + `timestamp` आवश्यक है।

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

**फ़ील्ड मैपिंग मुख्य बिंदु:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (इकाई पहले से फ़ेन है, रूपांतरण आवश्यक नहीं)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- रिपोर्ट में `cost` (फ़ेन)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: उमेंग एडाप्टर बनाएँ

**फ़ाइलें:**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### एडाप्टर विशिष्टता

उमेंग (Umeng U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- प्रमाणीकरण: API Key + API Secret + MD5 सिग्नेचर
- उमेंग **प्रमोशन प्रभाव निगरानी** पर केंद्रित है, विज्ञापन डिलीवरी प्लेटफ़ॉर्म से भिन्न — यह सीधे विज्ञापन प्लान नहीं बनाता/प्रबंधित करता, बल्कि विभिन्न चैनलों के प्रमोशन डेटा ट्रैक करता है
- capabilities: `['report', 'oauth']` (campaign/create/update/toggle समर्थित नहीं)
- रिपोर्ट इंटरफ़ेस: `/v1/ad_analytics/report` चैनल/दिनांक आयाम के अनुसार प्रमोशन डेटा लौटाता है
- fetchCampaigns खाली लौटाता है (उमेंग स्वयं प्लान नहीं बनाता)
- fetchReports प्रमोशन प्रभाव डेटा को एकीकृत रिपोर्ट मॉडल में मैप करता है

### उमेंग सिग्नेचर एल्गोरिथम

```
sign = md5(method + url + body + api_secret)
```

HTTP Header के माध्यम से प्रमाणीकरण जानकारी भेजें: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`।

**फ़ील्ड मैपिंग मुख्य बिंदु:**
- `channel` → `platform_campaign_id` (चैनल पहचानकर्ता को प्लान आयाम में मैप करें)
- `pv` → `impressions` (इंप्रेशन)
- `click` → `clicks` (क्लिक)
- `activation` → `conversions` (एक्टिवेशन/रूपांतरण)
- `cost` इकाई: युआन → फ़ेन (×100)

---

## Task 15: कुआइशौ मैगनेट इंजन एडाप्टर बनाएँ

**फ़ाइलें:**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### एडाप्टर विशिष्टता

कुआइशौ मैगनेट इंजन (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- प्रमाणीकरण: `access_token` Header
- विज्ञापन प्लान: `/campaign/list` + `/campaign/create` + `/campaign/update`
- रिपोर्ट: `/report/campaign/report` (सिंक्रोनस रिटर्न)
- मुद्रा इकाई: युआन → फ़ेन (×100)

**फ़ील्ड मैपिंग मुख्य बिंदु:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (युआन→फ़ेन ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- रिपोर्ट में `charge`→`cost` (युआन→फ़ेन)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: शियाओहोंगशू डंडेलियन एडाप्टर बनाएँ

**फ़ाइलें:**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### एडाप्टर विशिष्टता

शियाओहोंगशू डंडेलियन (小红书聚光平台):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- प्रमाणीकरण: `access_token` Header (`Authorization: Bearer xxx`)
- विज्ञापन प्लान: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- रिपोर्ट: `/v1/report/campaign/report`
- मुद्रा इकाई: फ़ेन (शियाओहोंगशू API फ़ेन में लौटाता है, रूपांतरण आवश्यक नहीं)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**फ़ील्ड मैपिंग मुख्य बिंदु:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (इकाई: फ़ेन)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- रिपोर्ट में `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## स्वीकृति मानदंड

1. ✅ टेनसेंट विज्ञापन एडाप्टर सभी 13 PlatformAdapter विधियाँ लागू करता है
2. ✅ उमेंग एडाप्टर report + oauth क्षमताएँ लागू करता है (उमेंग डिलीवरी ऑपरेशन समर्थित नहीं)
3. ✅ कुआइशौ मैगनेट इंजन एडाप्टर सभी 13 विधियाँ लागू करता है
4. ✅ शियाओहोंगशू डंडेलियन एडाप्टर सभी 13 विधियाँ लागू करता है
5. ✅ सभी 4 एडाप्टर bootstrap.php में पंजीकृत हैं
6. ✅ `GET /api/v1/platforms` 7 प्लेटफ़ॉर्म लौटाता है (पिछले 3 सहित)
7. ✅ सभी एडाप्टर curl कॉल में सही त्रुटि हैंडलिंग (curl_errno + CURLOPT_CONNECTTIMEOUT)
