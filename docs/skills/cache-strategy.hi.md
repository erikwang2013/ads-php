# कैश रणनीति

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

त्रि-स्तरीय कैश लागू करें (L1 मेमोरी → L2 APCu → L3 Redis)।

## CacheService उपयोग

```php
use erik\support\CacheService;

// 记忆模式：命中返回缓存，miss 执行回调并缓存
$data = CacheService::remember('cache:key', 300, function () {
    return heavyQuery();
});

// 手动刷新
CacheService::forget('cache:key');
CacheService::flush('cache:dashboard:');
```

## त्रि-स्तरीय कैश सिद्धांत

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## कैश TTL सुझाव

| एंडपॉइंट प्रकार | TTL | कारण |
|----------|-----|------|
| प्लेटफ़ॉर्म सूची | 3600s (1h) | प्लेटफ़ॉर्म मेटाडेटा शायद ही बदलता है |
| खाता सूची/विवरण | 300s (5min) | बार-बार सिंक हो सकता है |
| डैशबोर्ड सारांश | 300s (5min) | दैनिक डेटा |
| अलर्ट नियम | 120s (2min) | कुछ विलंब की अनुमति |
| अलर्ट अपठित संख्या | 30s | फ्रंटएंड 30s पोलिंग |

## Dashboard कैश रिफ़्रेश

DataSyncTask नया डेटा सिंक करने के बाद स्वचालित रूप से कॉल करता है:

```php
CacheService::flush('cache:dashboard:');
```

## सावधानियाँ

1. लिखने के ऑपरेशन के बाद संबंधित कैश रिफ़्रेश करना अनिवार्य है (जैसे destroy/sync के बाद `CacheService::forget()`)
2. APCu के लिए एक्सटेंशन इंस्टॉल करें: `apt install php-apcu`
3. कैश Key नामकरण मानक: `cache:{domain}:{tenantId}:{params}`
