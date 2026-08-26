# Стратегия кэширования

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Реализация трёхуровневого кэша (L1 память → L2 APCu → L3 Redis).

## Использование CacheService

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

## Принцип трёхуровневого кэша

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Рекомендации по TTL кэша

| Тип эндпоинта | TTL | Причина |
|----------|-----|------|
| Список платформ | 3600s (1ч) | Метаданные платформ редко меняются |
| Список/детали аккаунтов | 300s (5мин) | Могут часто синхронизироваться |
| Сводка дашборда | 300s (5мин) | Данные за день |
| Правила оповещений | 120s (2мин) | Допустима некоторая задержка |
| Непрочитанные оповещения | 30s | Фронтенд опрашивает каждые 30с |

## Обновление кэша дашборда

DataSyncTask автоматически вызывает после завершения синхронизации новых данных:

```php
CacheService::flush('cache:dashboard:');
```

## Примечания

1. После операций записи необходимо обновлять связанные кэши (например, после destroy/sync вызывать `CacheService::forget()`)
2. Для APCu требуется расширение: `apt install php-apcu`
3. Правило именования ключей кэша: `cache:{domain}:{tenantId}:{params}`
