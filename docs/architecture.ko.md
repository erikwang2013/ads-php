# 아키텍처 설계 문서

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. 시스템 개요

다중 플랫폼 광고 관리 시스템으로, **29개 광고 플랫폼**을 연동하며 집행 관리, 플랫폼 간 보고서, 경보 모니터링, 자동 입찰, 타겟팅을 지원합니다. SaaS 멀티 테넌트, 대행 운영, 자체 사용 3가지 모드를 지원합니다.

---

## 2. 배포 아키텍처

```
                         ┌──────────────────────────┐
                         │  客户端                   │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 插件         │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 张表  │      │ 缓存/队列│      │ 搜索索引  │
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. 요청 처리 파이프라인

### 3.1 Service 측 (15개 미들웨어)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
  → VersionMiddleware         (X-API-Version 版本路由)
  → RateLimitMiddleware       (Redis 滑动窗口 60次/60s)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟锁定)
  → SessionLimitMiddleware    (并发会话限制 最大3个活跃Token)
  → SqlGuardMiddleware        (SQL 注入模式检测)
  → ValidationMiddleware      (输入 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time 头 + 慢请求日志)
  → EncryptionMiddleware      (X-Encrypted 请求解密/响应加密)
  → AuthMiddleware            (JWT Bearer Token + IP/UA 绑定)
  → Controller
```

### 3.2 Admin 측 (6개 미들웨어)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → VersionMiddleware         (API 版本)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. 디렉터리 구조

```
ads-php/
├── service/                               # 业务 API 服务 :8788
│   ├── config/                            # 全局配置
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line 双模式)
│   │   ├── middleware.php                 # 11 层全局中间件
│   │   ├── exception.php                  # API 异常处理器
│   │   └── scout.php                      # ES 配置
│   ├── support/                           # 共享工具类 (erik\support)
│   │   ├── ApiResponse.php                # 统一 JSON 响应
│   │   ├── ControllerTrait.php            # 控制器公共 trait
│   │   ├── JwtService.php                 # JWT 包装 (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis 缓存
│   │   ├── HashidsService.php             # ID 加解密
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 异常渲染
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 层
│   │   │   ├── controller/v1/             # 14 个控制器
│   │   │   ├── middleware/                # 7 个中间件
│   │   │   ├── config/route.php           # 45+ 路由
│   │   │   └── route_helpers.php          # versioned() 版本路由
│   │   ├── ads-platform/                  # 平台适配器核心
│   │   │   ├── adapter/                   # 29 个平台适配器
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + 性能索引
│   │   ├── ads-account/                   # OAuth 账户 + 平台账户
│   │   ├── ads-task/                      # 5 个 cron 任务
│   │   ├── ads-alert/                     # 告警引擎 + 通知
│   │   ├── ads-report/                    # 报表引擎 (CSV/Excel/PDF)
│   │   └── ads-tenant/                    # 多租户
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # 中间件测试
│   │   ├── Unit/Task/                     # 任务测试 (规划)
│   │   └── Integration/                   # 控制器集成测试
│   └── start.php                          # 入口
├── admin/                                 # 管理后台 :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 页面 (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 个 API 客户端
│   │       ├── stores/                    # 4 个 Pinia Store
│   │       └── components/                # ListPageLayout 等共享组件
│   └── config/                            # Admin 配置
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 功能页面 + Shell 布局
│   │       ├── config/menu_config.dart    # 两级菜单 + 面包屑
│   │       ├── router.dart                # GoRouter + ShellRoute + 路由守卫
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + 平台检测
│   └── harmonyos/                         # HarmonyOS (API Client 就绪)
├── docker/                                # Nginx 配置 + Dockerfiles
├── .github/workflows/                     # CI (语法→测试→TS→Docker) + CD (构建推送)
└── docs/                                  # 设计文档
```

---

## 5. 데이터 모델

### 5.1 테이블 분류

| 분류 | 테이블 이름 | 기본 키 | 용도 |
|------|------|------|------|
| 기반 | `erik_tenants` | BIGINT Snowflake | 멀티 테넌트 |
| 계정 | `erik_platform_accounts`, `erik_auth_tokens` | BIGINT Snowflake | OAuth 플랫폼 계정 |
| 집행 계층 | `erik_campaigns`, `erik_ad_groups`, `erik_creatives` | BIGINT Snowflake | 광고 집행 |
| 보고서 | `erik_report_metrics`, `erik_report_extras` | BIGINT Snowflake | 통일 지표 |
| 경보 | `erik_alert_rules`, `erik_alert_logs` | BIGINT Snowflake | 모니터링 경보 |
| 입찰 | `erik_bid_rules`, `erik_bid_logs` | BIGINT Snowflake | 자동 입찰 |
| 타겟팅 | `erik_targeting_templates` | BIGINT Snowflake | 타겟팅 템플릿 |
| 소재 | `erik_assets` | BIGINT Snowflake | 소재 라이브러리 |
| 알림 | `erik_notifications` | BIGINT Snowflake | 사내 알림 |
| 기여도 | `erik_conversions`, `erik_attribution_results` | BIGINT Snowflake | 전환 추적 + 기여도 |
| 시스템 | `erik_sync_errors` | BIGINT Snowflake | 동기화 오류 |
| 관리 | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + 감사 |

### 5.2 네이밍 규칙

- 테이블 접두사: `erik_`
- 기본 키: `BIGINT UNSIGNED PRIMARY KEY` (자동 증가 없음, Snowflake ID)
- 엔진: InnoDB, 문자셋: utf8mb4
- 타임스탬프: `created_at`, `updated_at` (DATETIME)

---

## 6. 보안 아키텍처

### 6.1 보호 계층

| 계층 | 메커니즘 | 적용 범위 |
|----|------|----------|
| 전송 | Nginx (SSL 종단) | 전체 |
| 네트워크 | CORS 화이트리스트 + Origin 검증 + HSTS | Service |
| 입력 | AttackGuard (XSS 11패턴/경로 탐색 7패턴/Header 주입) | Service + Admin |
| 주입 | SQLGuard (SQL 주입 패턴 검출) | Service |
| 정제 | ValidationMiddleware (strip_tags) | Service |
| 인증 | JWT Bearer + bcrypt + IP/UA 바인딩 + refresh 순환 | Service |
| 인증 | Session + JWT 이중 채널 + CSRF Token | Admin |
| 권한 | RBAC (역할 + 권한 JSON) | Admin |
| 스로틀 | RateLimit (슬라이딩 윈도우) + LoginThrottle (5회→15분) | Service + Admin |
| 세션 | SessionLimit (최대 3개 활성 Token) + 블랙리스트 | Service |
| 암호화 | EncryptionMiddleware (전송) + Encryptable (저장) | Service |
| 리플레이 | ReplayGuard (Nonce+Timestamp ±5min, 비브라우저 측) | Service + 클라이언트 |
| 탄력성 | CircuitBreaker (플랫폼별: 5회 실패 → OPEN → 30초 반개 프로브) + GuardedAdapter (장애 시 fast-fail) | Service |
| 감사 | 작업 추적 (IP/UA/플랫폼) | Admin |
| 마스킹 | 로그 민감 필드 마스킹 (password/token/secret → ***) | Service |

### 6.2 클라이언트 플랫폼 식별

`X-Client-Platform` header로 식별:

| 값 | 출처 |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. API 버전 라우팅 메커니즘

버전 번호는 URL 경로에 나타나지 않습니다. 버전은 `X-API-Version` header로 전달되며, `VersionMiddleware`가 이를 읽어 `$request->apiVersion`에 설정합니다. `versioned()` 헬퍼 함수가 런타임에 컨트롤러 클래스의 버전 세그먼트를 요청 버전으로 교체합니다.

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. 예약 작업 스케줄링

| 작업 | Cron | 기능 |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | 만료 OAuth Token 갱신 |
| DataSyncTask | `*/10 * * * *` | Campaigns→AdGroups→Creatives→Reports 동기화→캐시 삭제 |
| AlertCheckTask | `*/5 * * * *` | 경보 규칙 평가, 알림 트리거 |
| BidCheckTask | `*/10 * * * *` | 입찰 규칙 평가, 예산 조정/시작·중지 실행 |
| RetrySyncTask | `*/3 * * * *` | 실패 동기화 재시도 (최대 3회, 지수 백오프) |

---

## 9. Erik Stack 패키지 통합

| 패키지 | 통합 위치 | 용도 |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10개 Model (SnowflakeTrait) + admin helpers.php | 기본 키 생성 |
| `erikwang2013/hashids` | ApiResponse + 2개 Admin Controller | ID 인코딩 |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | 인증 토큰 |
| `erikwang2013/encryption` | EncryptionMiddleware | 전송 암복호화 |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Model | DB 필드 암호화 |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | ES 검색 |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | 국가 국기 |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | 슬라이더 캡차 |
| `hg/apidoc` | 어노테이션 → 문서 생성 (Web UI: :8788/apidoc) | API 문서 |

---

## 10. 고동시성 아키텍처

### 10.1 데이터베이스 계층

| 최적화 | 설명 |
|------|------|
| 읽기/쓰기 분리 | 마스터 `shared`（쓰기）+ 읽기 전용 복제본 `read_replica`（보고서/분석 쿼리） |
| 영속 연결 | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent`로 빈번한 TCP 핸드셰이크 방지 |
| 연결 워밍업 | worker 시작 시 `SELECT 1` 실행, 커넥션 풀 준비 후 요청 수신 |

### 10.2 캐시 계층

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 메시지 큐

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4개 채널: `sync` | `report` | `export` | `notification`

### 10.4 수평 확장

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive**: 32 장기 연결 재사용
- **failover**: `proxy_next_upstream` 자동 장애 조치, 2회 재시도
- **속도 제한**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 정적 리소스 CDN

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — js/css 파일 사전 압축
- 프로덕션 환경에서 CDN 연동 (CloudFront/Aliyun CDN)

---

## 11. 배포와 CI/CD

### Docker 서비스

| 서비스 | 포트 | 이미지 |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy
