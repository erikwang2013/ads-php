# Estratégia de cache

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Implementar cache de três níveis (L1 memória → L2 APCu → L3 Redis).

## Uso do CacheService

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

## Princípio do cache de três níveis

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Recomendações de TTL de cache

| Tipo de endpoint | TTL | Motivo |
|----------|-----|------|
| Lista de plataformas | 3600s (1h) | Os metadados das plataformas mudam muito raramente |
| Lista/detalhes de contas | 300s (5min) | Podem ser sincronizadas com frequência |
| Resumo do dashboard | 300s (5min) | Dados diários |
| Regras de alerta | 120s (2min) | Algum atraso é aceitável |
| Alertas não lidos | 30s | Polling de 30s do frontend |

## Atualização do cache do Dashboard

O DataSyncTask chama automaticamente após sincronizar novos dados:

```php
CacheService::flush('cache:dashboard:');
```

## Observações

1. Após operações de escrita, o cache relacionado deve ser atualizado (ex.: `CacheService::forget()` depois de destroy/sync)
2. O APCu requer a extensão: `apt install php-apcu`
3. Convenção de nomes de chave de cache: `cache:{domain}:{tenantId}:{params}`

