# 缓存策略

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Implementa la caché de tres niveles (L1 memoria → L2 APCu → L3 Redis).

## Uso de CacheService

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

## Principio de la caché de tres niveles

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Sugerencias de TTL de caché

| Tipo de endpoint | TTL | Motivo |
|----------|-----|------|
| Lista de plataformas | 3600s (1h) | Los metadatos de plataforma casi no cambian |
| Lista/detalle de cuentas | 300s (5min) | Puede sincronizarse con frecuencia |
| Resumen del panel de control | 300s (5min) | Datos diarios |
| Reglas de alerta | 120s (2min) | Se permite cierto retraso |
| No leídos de alertas | 30s | El frontend consulta cada 30s |

## Refresco de la caché del Dashboard

DataSyncTask la invoca automáticamente tras sincronizar nuevos datos:

```php
CacheService::flush('cache:dashboard:');
```

## Notas

1. Tras operaciones de escritura hay que refrescar la caché relacionada (p. ej. `CacheService::forget()` después de destroy/sync)
2. APCu requiere instalar la extensión: `apt install php-apcu`
3. Convención de nombres de claves de caché: `cache:{domain}:{tenantId}:{params}`
