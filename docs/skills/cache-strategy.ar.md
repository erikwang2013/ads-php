# استراتيجية التخزين المؤقت

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

تنفيذ تخزين مؤقت من ثلاثة مستويات (L1 الذاكرة → L2 APCu → L3 Redis).

## استخدام CacheService

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

## مبدأ التخزين ثلاثي المستويات

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## اقتراحات TTL للتخزين المؤقت

| نوع نقطة النهاية | TTL | السبب |
|----------|-----|------|
| قائمة المنصات | 3600 ثانية (ساعة) | بيانات المنصات الوصفية نادرًا ما تتغير |
| قائمة/تفاصيل الحسابات | 300 ثانية (5 دقائق) | قد تتم المزامنة بشكل متكرر |
| ملخص لوحة التحكم | 300 ثانية (5 دقائق) | بيانات يومية |
| قواعد التنبيهات | 120 ثانية (دقيقتان) | يسمح ببعض التأخير |
| عدد التنبيهات غير المقروءة | 30 ثانية | استطلاع الواجهة الأمامية كل 30 ثانية |

## تحديث ذاكرة لوحة التحكم

يقوم DataSyncTask تلقائيًا باستدعاء ما يلي بعد مزامنة البيانات الجديدة:

```php
CacheService::flush('cache:dashboard:');
```

## ملاحظات

1. يجب تحديث ذاكرة التخزين المؤقت ذات الصلة بعد عمليات الكتابة (مثل `CacheService::forget()` بعد destroy/sync)
2. يتطلب APCu تثبيت الإضافة: `apt install php-apcu`
3. اصطلاح تسمية مفاتيح التخزين: `cache:{domain}:{tenantId}:{params}`
