# Cache-Strategie

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Dreistufigen Cache implementieren (L1 Speicher → L2 APCu → L3 Redis).

## CacheService-Nutzung

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

## Funktionsprinzip des dreistufigen Caches

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Cache-TTL-Empfehlungen

| Endpunkttyp | TTL | Begründung |
|----------|-----|------|
| Plattformliste | 3600s (1h) | Plattform-Metadaten ändern sich selten |
| Kontoliste/-details | 300s (5min) | Kann häufig synchronisiert werden |
| Dashboard-Zusammenfassung | 300s (5min) | Tagesdaten |
| Alarmregeln | 120s (2min) | Geringe Verzögerung zulässig |
| Ungelesene Alarme | 30s | Frontend-Polling alle 30s |

## Dashboard-Cache-Aktualisierung

DataSyncTask ruft nach Abschluss der Synchronisierung neuer Daten automatisch auf:

```php
CacheService::flush('cache:dashboard:');
```

## Hinweise

1. Nach Schreiboperationen müssen die zugehörigen Caches aktualisiert werden (z. B. nach destroy/sync `CacheService::forget()`)
2. APCu erfordert die Installation der Erweiterung: `apt install php-apcu`
3. Cache-Key-Namenskonvention: `cache:{domain}:{tenantId}:{params}`
