# 高并发优化

实施 8 项高并发优化策略。

## 1. 数据库读写分离

`service/config/database.php`：

```php
'connections' => [
    'shared' => [...]       // 主库（写）
    'read_replica' => [...] // 只读副本（报表查询）
]
```

环境变量：
```
DB_READ_HOST=replica.db.internal
DB_READ_USERNAME=readonly_user
```

## 2. 持久连接

```php
'options' => [
    \PDO::ATTR_PERSISTENT => true,  // worker 间复用连接
]
```

## 3. Redis 连接池

```php
'persistent' => true,
'read_write_timeout' => 3,
'connection_timeout' => 3,
```

## 4. 三级缓存

参见 [缓存策略技能](cache-strategy.md)。

## 5. 消息队列异步

```php
use erik\support\AsyncJobService;

// 将耗时操作推入队列
AsyncJobService::dispatch('sync', ['account_id' => 123]);

// worker 消费队列
AsyncJobService::processQueue('sync', function (array $payload) {
    // 执行耗时操作
});
```

4 个通道: `sync`, `report`, `export`, `notification`

## 6. Nginx 多层限流

```
# IP 限流: 30r/s + burst 20
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;

# 并发连接限制
limit_conn_zone $binary_remote_addr zone=conn:10m;
limit_conn conn 20;
```

## 7. 水平扩展

```
upstream service_api {
    server php:8788 weight=1 max_fails=3 fail_timeout=30s;
    server php2:8788 weight=1 max_fails=3 fail_timeout=30s;  # 新增实例
    keepalive 32;
}
```

## 8. CDN 静态资源

```nginx
location ~* \.(js|css|png|jpg)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
    gzip_static on;
}
```
