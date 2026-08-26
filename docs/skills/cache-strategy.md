# 缓存策略

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

实现三级缓存（L1 内存 → L2 APCu → L3 Redis）。

## CacheService 使用

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

## 三级缓存原理

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## 缓存 TTL 建议

| 端点类型 | TTL | 理由 |
|----------|-----|------|
| 平台列表 | 3600s (1h) | 平台元数据极少变化 |
| 账户列表/详情 | 300s (5min) | 可能频繁同步 |
| 仪表盘汇总 | 300s (5min) | 每日数据 |
| 告警规则 | 120s (2min) | 允许一定延迟 |
| 告警未读数 | 30s | 前端 30s 轮询 |

## Dashboard 缓存刷新

DataSyncTask 同步完成新数据后自动调用：

```php
CacheService::flush('cache:dashboard:');
```

## 注意事项

1. 写操作后必须刷新相关缓存（如 destroy/sync 后 `CacheService::forget()`）
2. APCu 需要安装扩展：`apt install php-apcu`
3. 缓存 Key 命名规范：`cache:{domain}:{tenantId}:{params}`
