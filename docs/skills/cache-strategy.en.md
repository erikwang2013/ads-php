# Cache Strategy

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Implement the 3-tier cache (L1 memory → L2 APCu → L3 Redis).

## CacheService Usage

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

## How the 3-Tier Cache Works

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Cache TTL Recommendations

| Endpoint Type | TTL | Reason |
|---------------|-----|--------|
| Platform list | 3600s (1h) | Platform metadata rarely changes |
| Account list/detail | 300s (5min) | May sync frequently |
| Dashboard summary | 300s (5min) | Daily data |
| Alert rules | 120s (2min) | Some latency acceptable |
| Alert unread count | 30s | Frontend polls every 30s |

## Dashboard Cache Refresh

Called automatically by DataSyncTask after syncing new data:

```php
CacheService::flush('cache:dashboard:');
```

## Notes

1. Must flush related caches after write operations (e.g. `CacheService::forget()` after destroy/sync)
2. APCu requires the extension: `apt install php-apcu`
3. Cache key naming convention: `cache:{domain}:{tenantId}:{params}`
