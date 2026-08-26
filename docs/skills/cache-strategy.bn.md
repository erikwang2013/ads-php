# ক্যাশ স্ট্র্যাটেজি

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

থ্রি-লেভেল ক্যাশ বাস্তবায়ন (L1 মেমরি → L2 APCu → L3 Redis)。

## CacheService ব্যবহার

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

## থ্রি-লেভেল ক্যাশ নীতি

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## ক্যাশ TTL পরামর্শ

| এন্ডপয়েন্ট টাইপ | TTL | কারণ |
|----------|-----|------|
| প্ল্যাটফর্ম লিস্ট | 3600s (1h) | প্ল্যাটফর্ম মেটাডেটা খুব কম পরিবর্তিত হয় |
| অ্যাকাউন্ট লিস্ট/ডিটেইল | 300s (5min) | ঘন ঘন সিঙ্ক হতে পারে |
| ড্যাশবোর্ড সামারি | 300s (5min) | দৈনিক ডেটা |
| অ্যালার্ট রুল | 120s (2min) | কিছুটা লেটেন্সি গ্রহণযোগ্য |
| অ্যালার্ট আনরিড কাউন্ট | 30s | ফ্রন্টএন্ড 30s পোলিং |

## Dashboard ক্যাশ রিফ্রেশ

DataSyncTask নতুন ডেটা সিঙ্ক শেষে অটো কল করে:

```php
CacheService::flush('cache:dashboard:');
```

## সতর্কতা

1. রাইট অপারেশনের পর সংশ্লিষ্ট ক্যাশ রিফ্রেশ করা আবশ্যক (যেমন destroy/sync এর পর `CacheService::forget()`)
2. APCu এক্সটেনশন ইনস্টল প্রয়োজন: `apt install php-apcu`
3. ক্যাশ Key নেমিং কনভেনশন: `cache:{domain}:{tenantId}:{params}`
