# 고동시성 최적화

[中文](docs/skills/high-concurrency.md) | [English](docs/skills/high-concurrency.en.md) | [한국어](docs/skills/high-concurrency.ko.md) | [Русский](docs/skills/high-concurrency.ru.md) | [Deutsch](docs/skills/high-concurrency.de.md) | [Français](docs/skills/high-concurrency.fr.md) | [Español](docs/skills/high-concurrency.es.md) | [Português](docs/skills/high-concurrency.pt.md) | [हिन्दी](docs/skills/high-concurrency.hi.md) | [العربية](docs/skills/high-concurrency.ar.md) | [বাংলা](docs/skills/high-concurrency.bn.md) | [Bahasa Indonesia](docs/skills/high-concurrency.id.md) | [日本語](docs/skills/high-concurrency.ja.md)

8개 고동시성 최적화 전략을 시행합니다.

## 1. DB 읽기/쓰기 분리

`service/config/database.php`:

```php
'connections' => [
    'shared' => [...]       // 마스터 DB (쓰기)
    'read_replica' => [...] // 읽기 전용 복제본 (보고서 쿼리)
]
```

환경 변수:
```
DB_READ_HOST=replica.db.internal
DB_READ_USERNAME=readonly_user
```

## 2. 영속 연결

```php
'options' => [
    \PDO::ATTR_PERSISTENT => true,  // worker 간 연결 재사용
]
```

## 3. Redis 커넥션 풀

```php
'persistent' => true,
'read_write_timeout' => 3,
'connection_timeout' => 3,
```

## 4. 3단계 캐시

[캐시 전략 스킬](cache-strategy.ko.md) 참조.

## 5. 메시지 큐 비동기

```php
use erik\support\AsyncJobService;

// 오래 걸리는 작업을 큐에 푸시
AsyncJobService::dispatch('sync', ['account_id' => 123]);

// worker가 큐 소비
AsyncJobService::processQueue('sync', function (array $payload) {
    // 오래 걸리는 작업 실행
});
```

4개 채널: `sync`, `report`, `export`, `notification`

## 6. Nginx 다층 속도 제한

```
# IP 속도 제한: 30r/s + burst 20
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;

# 동시 연결 제한
limit_conn_zone $binary_remote_addr zone=conn:10m;
limit_conn conn 20;
```

## 7. 수평 확장

```
upstream service_api {
    server php:8788 weight=1 max_fails=3 fail_timeout=30s;
    server php2:8788 weight=1 max_fails=3 fail_timeout=30s;  # 새 인스턴스
    keepalive 32;
}
```

## 8. CDN 정적 리소스

```nginx
location ~* \.(js|css|png|jpg)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
    gzip_static on;
}
```
