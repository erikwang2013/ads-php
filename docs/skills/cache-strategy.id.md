# Strategi Cache

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

Implementasikan cache tiga tingkat (L1 memori → L2 APCu → L3 Redis).

## Penggunaan CacheService

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

## Prinsip Cache Tiga Tingkat

```
L1: 进程内存数组 (< 1µs)
  命中 → 直接返回，最快但仅限当前 worker

L2: APCu 共享内存 (< 100µs)
  命中 → 回填 L1，进程间共享

L3: Redis 持久缓存 (< 1ms)
  命中 → 回填 L1 + L2，跨服务器共享
  miss → 执行回调 → 写入 L1+L2+L3
```

## Saran TTL Cache

| Tipe endpoint | TTL | Alasan |
|----------|-----|------|
| Daftar platform | 3600s (1 jam) | Metadata platform sangat jarang berubah |
| Daftar/detail akun | 300s (5 menit) | Mungkin sering disinkronkan |
| Ringkasan dasbor | 300s (5 menit) | Data harian |
| Aturan peringatan | 120s (2 menit) | Mengizinkan sedikit penundaan |
| Jumlah peringatan belum dibaca | 30s | Frontend polling 30 detik |

## Refresh Cache Dashboard

DataSyncTask otomatis memanggil setelah sinkronisasi data baru selesai:

```php
CacheService::flush('cache:dashboard:');
```

## Catatan

1. Setelah operasi tulis harus refresh cache terkait (mis. setelah destroy/sync `CacheService::forget()`)
2. APCu perlu instalasi ekstensi: `apt install php-apcu`
3. Konvensi penamaan Key cache: `cache:{domain}:{tenantId}:{params}`
