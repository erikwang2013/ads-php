# उच्च समवर्ती अनुकूलन

[中文](docs/skills/high-concurrency.md) | [English](docs/skills/high-concurrency.en.md) | [한국어](docs/skills/high-concurrency.ko.md) | [Русский](docs/skills/high-concurrency.ru.md) | [Deutsch](docs/skills/high-concurrency.de.md) | [Français](docs/skills/high-concurrency.fr.md) | [Español](docs/skills/high-concurrency.es.md) | [Português](docs/skills/high-concurrency.pt.md) | [हिन्दी](docs/skills/high-concurrency.hi.md) | [العربية](docs/skills/high-concurrency.ar.md) | [বাংলা](docs/skills/high-concurrency.bn.md) | [Bahasa Indonesia](docs/skills/high-concurrency.id.md) | [日本語](docs/skills/high-concurrency.ja.md)

8 उच्च समवर्ती अनुकूलन रणनीतियाँ लागू करें।

## 1. डेटाबेस रीड/राइट सेपरेशन

`service/config/database.php`：

```php
'connections' => [
    'shared' => [...]       // 主库（写）
    'read_replica' => [...] // 只读副本（报表查询）
]
```

एनवायरनमेंट वेरिएबल्स：
```
DB_READ_HOST=replica.db.internal
DB_READ_USERNAME=readonly_user
```

## 2. पर्सिस्टेंट कनेक्शन

```php
'options' => [
    \PDO::ATTR_PERSISTENT => true,  // worker 间复用连接
]
```

## 3. Redis कनेक्शन पूल

```php
'persistent' => true,
'read_write_timeout' => 3,
'connection_timeout' => 3,
```

## 4. त्रि-स्तरीय कैश

[कैश रणनीति स्किल](cache-strategy.hi.md) देखें।

## 5. मैसेज क्यू एसिंक्रोनस

```php
use erik\support\AsyncJobService;

// 将耗时操作推入队列
AsyncJobService::dispatch('sync', ['account_id' => 123]);

// worker 消费队列
AsyncJobService::processQueue('sync', function (array $payload) {
    // 执行耗时操作
});
```

4 चैनल: `sync`, `report`, `export`, `notification`

## 6. Nginx बहु-स्तरीय रेट-लिमिट

```
# IP 限流: 30r/s + burst 20
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;

# 并发连接限制
limit_conn_zone $binary_remote_addr zone=conn:10m;
limit_conn conn 20;
```

## 7. क्षैतिज स्केलिंग

```
upstream service_api {
    server php:8788 weight=1 max_fails=3 fail_timeout=30s;
    server php2:8788 weight=1 max_fails=3 fail_timeout=30s;  # 新增实例
    keepalive 32;
}
```

## 8. CDN स्टैटिक एसेट

```nginx
location ~* \.(js|css|png|jpg)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
    gzip_static on;
}
```
