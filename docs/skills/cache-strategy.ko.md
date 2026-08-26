# 캐시 전략

[中文](docs/skills/cache-strategy.md) | [English](docs/skills/cache-strategy.en.md) | [한국어](docs/skills/cache-strategy.ko.md) | [Русский](docs/skills/cache-strategy.ru.md) | [Deutsch](docs/skills/cache-strategy.de.md) | [Français](docs/skills/cache-strategy.fr.md) | [Español](docs/skills/cache-strategy.es.md) | [Português](docs/skills/cache-strategy.pt.md) | [हिन्दी](docs/skills/cache-strategy.hi.md) | [العربية](docs/skills/cache-strategy.ar.md) | [বাংলা](docs/skills/cache-strategy.bn.md) | [Bahasa Indonesia](docs/skills/cache-strategy.id.md) | [日本語](docs/skills/cache-strategy.ja.md)

3단계 캐시 구현 (L1 메모리 → L2 APCu → L3 Redis).

## CacheService 사용

```php
use erik\support\CacheService;

// remember 모드: hit 시 캐시 반환, miss 시 콜백 실행 후 캐시
$data = CacheService::remember('cache:key', 300, function () {
    return heavyQuery();
});

// 수동 갱신
CacheService::forget('cache:key');
CacheService::flush('cache:dashboard:');
```

## 3단계 캐시 원리

```
L1: 프로세스 메모리 배열 (< 1µs)
  hit → 직접 반환, 가장 빠르지만 현재 worker에 한정

L2: APCu 공유 메모리 (< 100µs)
  hit → L1에 백필, 프로세스 간 공유

L3: Redis 영속 캐시 (< 1ms)
  hit → L1 + L2에 백필, 서버 간 공유
  miss → 콜백 실행 → L1+L2+L3 저장
```

## 캐시 TTL 권장

| 엔드포인트 유형 | TTL | 이유 |
|----------|-----|------|
| 플랫폼 목록 | 3600s (1h) | 플랫폼 메타데이터는 거의 변하지 않음 |
| 계정 목록/상세 | 300s (5min) | 빈번한 동기화 가능성 |
| 대시보드 집계 | 300s (5min) | 일일 데이터 |
| 경보 규칙 | 120s (2min) | 일부 지연 허용 |
| 경보 미확인 수 | 30s | 프론트엔드 30s 폴링 |

## Dashboard 캐시 갱신

DataSyncTask가 새 데이터 동기화 완료 후 자동 호출:

```php
CacheService::flush('cache:dashboard:');
```

## 주의 사항

1. 쓰기 작업 후 관련 캐시를 반드시 갱신 (예: destroy/sync 후 `CacheService::forget()`)
2. APCu는 확장 설치 필요: `apt install php-apcu`
3. 캐시 Key 네이밍 규칙: `cache:{domain}:{tenantId}:{params}`
