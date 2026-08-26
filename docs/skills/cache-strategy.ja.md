# キャッシュ戦略

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

3 段キャッシュ（L1 メモリ → L2 APCu → L3 Redis）を実装します。

## CacheService の使用

```php
use erik\support\CacheService;

// 記憶モード：ヒットならキャッシュを返し、ミスならコールバックを実行してキャッシュ
$data = CacheService::remember('cache:key', 300, function () {
    return heavyQuery();
});

// 手動リフレッシュ
CacheService::forget('cache:key');
CacheService::flush('cache:dashboard:');
```

## 3 段キャッシュの原理

```
L1: プロセスメモリ配列 (< 1µs)
  ヒット → 直接返却、最速だが現在の worker のみ

L2: APCu 共有メモリ (< 100µs)
  ヒット → L1 にバックフィル、プロセス間で共有

L3: Redis 永続キャッシュ (< 1ms)
  ヒット → L1 + L2 にバックフィル、サーバー間で共有
  ミス → コールバックを実行 → L1+L2+L3 に書き込み
```

## キャッシュ TTL 推奨

| エンドポイント種別 | TTL | 理由 |
|----------|-----|------|
| プラットフォームリスト | 3600s (1h) | プラットフォームメタデータはほとんど変化しない |
| アカウントリスト/詳細 | 300s (5min) | 頻繁に同期される可能性 |
| ダッシュボード集計 | 300s (5min) | 日次データ |
| アラートルール | 120s (2min) | ある程度の遅延を許容 |
| アラート未読数 | 30s | フロントエンド 30s ポーリング |

## Dashboard キャッシュのリフレッシュ

DataSyncTask が新データの同期完了後に自動的に呼び出し:

```php
CacheService::flush('cache:dashboard:');
```

## 注意事項

1. 書き込み操作後は関連キャッシュを必ずリフレッシュ（destroy/sync 後は `CacheService::forget()` など）
2. APCu は拡張のインストールが必要: `apt install php-apcu`
3. キャッシュ Key 命名規則: `cache:{domain}:{tenantId}:{params}`
