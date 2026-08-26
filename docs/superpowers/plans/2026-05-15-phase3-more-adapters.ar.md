# المرحلة 3: خطة تنفيذ توسيع محولات منصات الإعلانات

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **للوكلاء العاملين:** مهارة فرعية مطلوبة: استخدم superpowers:subagent-driven-development للتنفيذ.

**الهدف:** إضافة محولات لأربع منصات: إعلانات تينسنت (腾讯广告)، و友盟 (Umeng)، وKuaishou Cili (快手磁力引擎)، وXiaohongshu Pugongying (小红书蒲公英).

**المحولات الحالية (المرحلتان 1+2):** 巨量引擎، 百度营销، 淘宝/阿里妈妈

**البنية:** ينفّذ كل محول واجهة `PlatformAdapter`، ويُسجّل في `AdapterRegistry`، ليصبح قابلاً للاستدعاء الموحد من خلال تدفق تفويض OAuth ومهام مزامنة البيانات ولوحة الإدارة الأمامية.

---

## المهمة 13: إنشاء محول إعلانات تينسنت

**الملفات:**
- إنشاء: `service/plugin/ads-platform/adapter/Tencent.php`
- تعديل: `service/plugin/ads-platform/config/bootstrap.php`

### مواصفات المحول

API إعلانات تينسنت (广点通):
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- قاعدة API: `https://api.e.qq.com/v3.0/`
- طريقة المصادقة: معامل URL `access_token` + `nonce`/`timestamp` لمنع إعادة التشغيل
- خطط الإعلان: `campaigns/get` + `campaigns/add` + `campaigns/update`
- التقارير: `daily_reports/get` (غير متزامن: إنشاء مهمة→استطلاع→جلب)
- وحدة المبلغ: فين (متوافقة مع النموذج الموحد، بدون تحويل)
- تعيين الحالات: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### توقيع API الخاص بتينسنت

تستخدم تينسنت `access_token` كمعامل URL، ولا تتطلب توقيع MD5، لكنها تتطلب `nonce` (رقم عشوائي) + `timestamp` لمنع إعادة التشغيل.

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

**نقاط تعيين الحقول:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (الوحدة فين بالفعل، بدون تحويل)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- في التقارير `cost` (فين)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## المهمة 14: إنشاء محول Umeng

**الملفات:**
- إنشاء: `service/plugin/ads-platform/adapter/Umeng.php`
- تعديل: `service/plugin/ads-platform/config/bootstrap.php`

### مواصفات المحول

友盟 (Umeng U-App + U-Ads):
- قاعدة API: `https://api.open.umeng.com/`
- طريقة المصادقة: API Key + API Secret + توقيع MD5
- تركز Umeng على **مراقبة فعالية الترويج**، وتختلف عن منصات شراء الإعلانات — فهي لا تنشئ/تدير خطط الإعلان مباشرة، بل تتعقب بيانات الترويج لكل قناة
- capabilities: `['report', 'oauth']` (لا تدعم campaign/create/update/toggle)
- واجهة التقارير: `/v1/ad_analytics/report` تُرجع بيانات الترويج حسب أبعاد القناة/التاريخ
- fetchCampaigns يُرجع فارغًا (Umeng لا تنشئ خططًا)
- fetchReports يجلب بيانات فعالية الترويج ويرسمها إلى نموذج التقارير الموحد

### خوارزمية توقيع Umeng

```
sign = md5(method + url + body + api_secret)
```

تمرير معلومات المصادقة عبر ترويسات HTTP: `X-Umeng-API-Key`، `X-Umeng-Sign`، `X-Umeng-Timestamp`.

**نقاط تعيين الحقول:**
- `channel` → `platform_campaign_id` (تعيين معرّف القناة إلى بُعد الخطة)
- `pv` → `impressions` (الظهور)
- `click` → `clicks` (النقرات)
- `activation` → `conversions` (التفعيل/التحويل)
- وحدة `cost`: يوان → فين (×100)

---

## المهمة 15: إنشاء محول Kuaishou Cili

**الملفات:**
- إنشاء: `service/plugin/ads-platform/adapter/Kuaishou.php`
- تعديل: `service/plugin/ads-platform/config/bootstrap.php`

### مواصفات المحول

快手磁力引擎 (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- قاعدة API: `https://api.e.kuaishou.com/v2/`
- طريقة المصادقة: ترويسة `access_token`
- خطط الإعلان: `/campaign/list` + `/campaign/create` + `/campaign/update`
- التقارير: `/report/campaign/report` (إرجاع متزامن)
- وحدة المبلغ: يوان → فين (×100)

**نقاط تعيين الحقول:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (يوان→فين ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- في التقارير `charge`→`cost` (يوان→فين)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## المهمة 16: إنشاء محول Xiaohongshu Pugongying

**الملفات:**
- إنشاء: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- تعديل: `service/plugin/ads-platform/config/bootstrap.php`

### مواصفات المحول

小红书蒲公英 (منصة Xiaohongshu Juguang):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- قاعدة API: `https://ark.xiaohongshu.com/api/open/`
- طريقة المصادقة: ترويسة `access_token` (`Authorization: Bearer xxx`)
- خطط الإعلان: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- التقارير: `/v1/report/campaign/report`
- وحدة المبلغ: فين (API Xiaohongshu يُرجع بالفين، بدون تحويل)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**نقاط تعيين الحقول:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (الوحدة: فين)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- في التقارير `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## معايير القبول

1. ✅ محول إعلانات تينسنت ينفّذ جميع طرق PlatformAdapter الـ 13
2. ✅ محول Umeng ينفّذ إمكانات report + oauth (Umeng لا تدعم عمليات الإطلاق)
3. ✅ محول Kuaishou Cili ينفّذ جميع الطرق الـ 13
4. ✅ محول Xiaohongshu Pugongying ينفّذ جميع الطرق الـ 13
5. ✅ المحولات الأربعة مسجلة جميعها في bootstrap.php
6. ✅ `GET /api/v1/platforms` يُرجع 7 منصات (بما فيها الثلاث السابقة)
7. ✅ جميع استدعاءات curl في المحولات تعالج الأخطاء بشكل صحيح (curl_errno + CURLOPT_CONNECTTIMEOUT)
