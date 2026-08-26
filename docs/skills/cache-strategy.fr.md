# Stratégie de cache

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Implémenter le cache à trois niveaux (L1 mémoire → L2 APCu → L3 Redis).

## Utilisation de CacheService

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

## Principe du cache à trois niveaux

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Suggestions de TTL de cache

| Type de point de terminaison | TTL | Justification |
|----------|-----|------|
| Liste des plateformes | 3600 s (1 h) | Les métadonnées de plateformes changent très rarement |
| Liste/détail des comptes | 300 s (5 min) | Synchronisation potentiellement fréquente |
| Récapitulatif du tableau de bord | 300 s (5 min) | Données quotidiennes |
| Règles d'alerte | 120 s (2 min) | Un certain délai est acceptable |
| Alertes non lues | 30 s | Interrogation toutes les 30 s par le frontend |

## Rafraîchissement du cache du tableau de bord

Appel automatique après que DataSyncTask a synchronisé de nouvelles données :

```php
CacheService::flush('cache:dashboard:');
```

## Points d'attention

1. Les opérations d'écriture doivent rafraîchir le cache associé (par exemple `CacheService::forget()` après destroy/sync)
2. APCu nécessite l'installation de l'extension : `apt install php-apcu`
3. Convention de nommage des clés de cache : `cache:{domain}:{tenantId}:{params}`
