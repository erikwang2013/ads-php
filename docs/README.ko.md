# Ads Platform — 다중 플랫폼 광고 관리 시스템

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 개요

**Ads Platform**은 **29개 광고 플랫폼**(국내 16개 + 국제 13개)을 연동하는 멀티 플랫폼 광고 관리 시스템으로, 광고 집행과 플랫폼 간 데이터 보고서를 통합 관리합니다.

- **캠페인 관리** — OAuth 계정 인증, 캠페인/광고그룹/크리에이티브 플랫폼 간 통합 관리
- **리포트** — 플랫폼 간 지표 집계, CSV/Excel/PDF 내보내기, 5개 모델 기여도 분석
- **스마트 집행** — 자동 입찰, 예산 경보, 집행 캘린더 (Gantt), 소재 라이브러리
- **글로벌 가속** — CDN 소재 배포 (멀티 드라이버: 로컬 / Alibaba Cloud OSS / Tencent Cloud COS / S3 호환, 관리자에서 멀티 프로바이더 설정)
- **모니터링 및 경보** — 경보 규칙 엔진, 다중 채널 푸시, 예약 자동 동기화
- **다중 단말 접근** — 웹 관리자 (Vue 3), Flutter PC/Mobile, HarmonyOS
- **안정성 및 신뢰성** — 플랫폼 호출 서킷 브레이커/다운그레이드/타임아웃, 3단계 캐시, 고동시성 최적화, 22개 보안 보호
- **국제화** — 12개 언어 문서, 중영 이중 언어 인터페이스

> 아키텍처 설계 → [docs/architecture.md](docs/architecture.ko.md)  
> 기능 모듈 → [docs/features.md](docs/features.ko.md)  
> API 문서 → [docs/api.md](docs/api.ko.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> 버전 비교 → [docs/versions.md](docs/versions.ko.md)（Lite 오픈소스 / Standard & Full 문의: erik@erik.xyz）

### 지원 플랫폼

#### 국내 (16)
| 플랫폼 | 어댑터 | 인증 |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + 信封签名 |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 URL参数 |
| 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 | Weibo | OAuth2 Bearer |
| B站花火 | Bilibili | OAuth2 Bearer |
| 优酷广告 | Youku | OAuth2 + MD5 |
| 美团广告 | Meituan | OAuth2 Bearer |
| 知乎广告 | Zhihu | OAuth2 Bearer |
| 360推广 | Qihoo360 | API Key + Sign |
| 搜狗推广 | Sogou | API Key + Sign |
| 友盟 | Umeng | API Key + MD5 |
| 京东京准通 | Jingdong | OAuth2 + MD5 |
| 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign |

#### 해외 (13)
| 플랫폼 | 어댑터 | 인증 |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL参数 |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## 기술 스택

| 계층 | 기술 | 설명 |
|----|------|------|
| 서버 | webman v2 + PHP 8.2+ | 8개 플러그인, 75+ API 엔드포인트 |
| 데이터베이스 | MySQL 8.0 | 29개 테이블, ads_ 접두사, Snowflake BIGINT 기본 키 |
| 캐시 | Redis 7 | 3단계 캐시 (L1 메모리/L2 APCu/L3 Redis), 속도 제한 카운터, Pub/Sub, 메시지 큐 |
| 검색 | Elasticsearch | webman-scout 자동 인덱스 동기화 (구성됨) |
| 관리 백엔드 | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP 백엔드(포트 8789), SPA에서 비즈니스 API 직접 호출(포트 8788), 19개 페이지, ECharts 시각화 |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile 반응형, Desktop Shell 레이아웃, 12개 페이지 |
| HarmonyOS | ArkTS + ArkUI | 6개 페이지 구현 완료, HTTP 클라이언트 준비 완료 |
| 배포 | Docker + Nginx + GHCR | Docker Compose 원클릭 시작, GitHub Actions 자동 빌드/푸시 |

## 아키텍처 다이어그램

![시스템 아키텍처 다이어그램](docs/diagrams/svg/architecture.ko.svg)

### 요청 흐름 다이어그램

![요청 흐름 다이어그램](docs/diagrams/svg/request-flow.ko.svg)

### 기능 모듈 다이어그램

![기능 모듈 다이어그램](docs/diagrams/svg/functional-modules.ko.svg)

### 데이터 수명주기 다이어그램

![데이터 수명주기 다이어그램](docs/diagrams/svg/data-lifecycle.ko.svg)

> 전체 버전에는 모든 세부 주석, Admin 파이프라인, 예약 작업 Gantt 차트, 캐시 상태 머신 포함 → [docs/diagrams/](docs/diagrams/) |

> 상세 아키텍처 설명, 보안 아키텍처, 고동시성 설계는 [아키텍처 설계 문서](docs/architecture.ko.md) 참조 | 과거 설계 명세는 [design.md](docs/superpowers/specs/design.ko.md) 참조

## 아키텍처 설명

- **`service/`** — webman v2 사용자 측 비즈니스 API 서비스, 포트 **8788** 리슨. 광고 플랫폼 연동, OAuth 인증, 데이터 동기화, 보고서 엔진, 경보 모니터링 등 비즈니스 로직 처리.
- **`admin/`** — webman-admin v2 독립 관리 백엔드, 포트 **8789** 리슨. PHP 백엔드(인증/권한, 사용자 관리, 시스템 설정)와 Vue 3 SPA 프론트엔드 포함.
- **관리 백엔드와 비즈니스 서비스 통신** — Vue SPA가 axios(baseURL `/api`)로 service API를 직접 호출; admin 전용 라우트(`/api/admin/*`)는 admin PHP 백엔드(8789)가 제공, Nginx가 경로별로 분기.
- **개발 모드** — Vite dev server (포트 5173)가 `/api`를 service:8788로 프록시; admin PHP 백엔드는 8789에서 session 인증과 SPA 정적 서비스 제공.
- **프로덕션 모드** — Nginx가 `/`를 admin:8789(관리 백엔드 SPA)로, `/api/`를 service:8788(비즈니스 API)로 라우팅.

## Erik Stack 통합

| 패키지 | 용도 |
|----|------|
| `erikwang2013/snowflake-php` | 분산 Snowflake ID 생성 |
| `erikwang2013/hashids` | API ID 파라미터 암복호화 |
| `erikwang2013/jwt-webman` | JWT 인증 토큰 |
| `erikwang2013/encryption` | API 계층 민감 데이터 암복호화 |
| `erikwang2013/encryptable` | DB 필드 단위 자동 암복호화 |
| `erikwang2013/webman-scout` | Elasticsearch 데이터 동기화 |
| `erikwang2013/season` | 국가 국기 식별 |
| `erikwang2013/poster-php` | 슬라이더 캡차 (로그인 보호) |
| `hg/apidoc` | API 문서 자동 생성 (어노테이션 + Web UI) |

## 국제화

전체 인터페이스가 **중국어 (zh-CN)** / **English (en)** 이중 언어 전환 지원:

| 단말 | 기술 | 전환 방식 |
|----|------|---------|
| Admin | vue-i18n v9 | TopBar 언어 드롭다운 메뉴, localStorage 영속화 |
| Service API | `erik\support\I18n` | Accept-Language 요청 헤더 / `?lang=` 파라미터 |
| Flutter | AppLocalizations + Delegate | 시스템 언어 자동 감지 |
| HarmonyOS | StringResources | `setLang()` 전환 |

## 보안

### Service 측 (14개 글로벌 + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware（라우트 계층）

### Admin 측 (10개 글로벌 + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck（라우트 계층）

### 보호 기능 총람 (22항목)

| 분류 | 보호 항목 | 설명 |
|------|--------|------|
| 입력 검출 | XSS (11패턴) | script/iframe/event handler/javascript:/data: |
| | 경로 탐색 (7패턴) | ../ / null byte / /etc/passwd / .env / .git |
| | Header 주입 | CRLF 검출 |
| | Body 크기 제한 | 10 MiB |
| | Content-Type 화이트리스트 | JSON/Form/Multipart/Plain |
| | SQL 주입 | UNION/DROP/ALTER 패턴 검출 |
| 인증 | JWT Token 바인딩 | IP + User-Agent hash 검증 |
| | Token 갱신 + 블랙리스트 | 기존 Token 자동 무효화 |
| | 로그인 스로틀 | 5회 실패 → 15분 잠금 (Redis) |
| | 동시 세션 제한 | 사용자당 최대 3개 활성 Token |
| | 캡차 | 슬라이더 캡차 (5분 유효, 5px 허용 오차) |
| 요청 검증 | CORS 화이트리스트 | 프로덕션 환경 도메인 화이트리스트 |
| | Origin/Referer 검증 | 크로스 도메인 출처 검증 |
| | CSRF Token | Admin 측 session token 검증 |
| | 리플레이 공격 방어 | Nonce + Timestamp ±5min (비브라우저 측) |
| | API 속도 제한 | 슬라이딩 윈도우 60회/60s |
| | SSRF 방어 | OAuth redirect_uri 화이트리스트 |
| 응답 헤더 | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | 클릭재킹 방어 + HTTPS 강제 |
| | X-Content-Type-Options | nosniff |
| 데이터 보호 | 전송 암호화 | EncryptionMiddleware (X-Encrypted) |
| | 저장 암호화 | Encryptable (DB 필드 단위) |
| | 로그 마스킹 | password/token/secret → \*\*\* |

### 보안 아키텍처 다이어그램

![보안 아키텍처 다이어그램](docs/diagrams/svg/security.ko.svg)

**심층 방어**: 외부 계층（Nginx）→ 진입 가드（5개 미들웨어）→ 신원 인증（7항목）→ 입력 검증（4항목）→ 빈도 제어 → 데이터 암호화 → 감사 추적

**인증**: 서버와 admin 모두 `admin_users` 테이블 + bcrypt 해시 통일, JWT 24h + refresh 순환

**감사**: 모든 작업에 IP / User-Agent / Client-Platform / 작업 상세 기록

**이중 확인**: 삭제/해제/일괄 작업은 "확인 단어 입력" 패턴 사용（`GlobalConfirm` + `useConfirmStore`）

---

## 고급 기능

| 기능 | 설명 | 기술 |
|------|------|------|
| 소재 라이브러리 | 이미지/영상 업로드 관리, 갤러리 미리보기, URL 복사 | AssetController + Vue 갤러리 |
| 예산 경보 | 일일 예산 소진 실시간 추적, 3단계 경보 (50/80/100%) | BudgetAlertService + 15min Cron |
| 집행 캘린더 | 플랫폼 간 Gantt 차트, 월/주 뷰, 플랫폼별 색상 | CalendarService + Vue Gantt |
| 플랫폼 간 기여도 | 5개 모델 기여도 (first/last/linear/time_decay/position_based), 30일 소급 | AttributionEngine + ECharts |
| 플래틴 호출 탄력성 | 플래틴별 서킷 브레이커 상태 머신 (5회 실패 → OPEN → 30초 half-open 프로브), 다운그레이드 fast-fail, 29개 어댑터 타임아웃 점검 | CircuitBreaker + GuardedAdapter |
| CDN 소재 가속 | 객체 스토리지 멀티 드라이버 (local/oss/cos/s3), 관리자 CDN 프로바이더 관리, 프리사인 직접 업로드, 삭제 시 자동 캐시 퍼지 | ads-storage 플러그인 + CdnProviderController |

---

## 고동시성

| 최적화 | 방안 | 파일 |
|------|------|------|
| DB 읽기/쓰기 분리 | 마스터 `shared` + 읽기 전용 복제본 `read_replica`, SELECT 자동 라우팅 | `config/database.php` |
| DB 커넥션 풀 | `PDO::ATTR_PERSISTENT` 영속 연결 + 타임존 초기화 워밍업 | `config/database.php` |
| Redis 커넥션 풀 | `persistent` 영속 연결 + 읽기/쓰기 분리 `readonly` 설정 | `config/redis.php` |
| 3단계 캐시 | L1 프로세스 메모리 → L2 APCu 공유 메모리 → L3 Redis | `support/CacheService.php` |
| 메시지 큐 비동기 | Redis List 4채널 (sync/report/export/notification) | `support/AsyncJobService.php` |
| Nginx 단계별 속도 제한 | 30r/s + burst 20 + 20 동시 연결 + keepalive 32 | `docker/nginx/admin.conf` |
| 수평 확장 | upstream 다중 인스턴스 + 장애 조치 + sticky session | `docker/nginx/admin.conf` |
| CDN 가속 | 정적 리소스 `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## 빠른 시작

### 원클릭 웹 설치 (권장)

서비스 시작 후 브라우저에서 `/install` 접속하여 설치 마법사 진입:

```bash
# 관리 백엔드 시작 (포트 8789)
cd admin && composer install && php start.php start

# 브라우저에서 http://localhost:8789/install 접속
# 설치 마법사에서 데이터베이스 정보, 관리자 계정 입력 후 「설치 시작」 클릭
```

설치 마법사가 웹에서 다음 단계를 안내합니다:
1. **데이터베이스 연결** — MySQL 호스트, 포트, 데이터베이스 이름, 사용자 이름/비밀번호 입력, 연결 테스트 지원
2. **Redis 설정** — Redis 연결 정보 입력 (선택 사항)
3. **관리자 계정** — 백엔드 로그인 사용자 이름, 비밀번호, 표시 이름 설정
4. **원클릭 설치** — 자동 DB 생성, `install.sql` 실행으로 29개 테이블 생성 + 시드 데이터 작성, 관리자 비밀번호 업데이트

설치 완료 후 `/` 접속하여 관리 백엔드 진입, 설정한 사용자 이름과 비밀번호로 로그인.

### Docker (프로덕션 환경 권장)

```bash
# 전체 서비스 시작 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 데이터베이스 초기화 (테이블 생성 + 시드 데이터)
make db-init

# 접속
# 관리 백엔드: http://localhost
# 설치 마법사: http://localhost/install
# API: http://localhost/api（Header: X-API-Version: v1）
```

### 로컬 개발

```bash
# 서버 (포트 8788)
cd service && composer install && php start.php start

# 관리 백엔드 (포트 5173)
cd admin/public/web && npm install && npm run dev

# Flutter App
cd apps/flutter && flutter run -d chrome  # Web PC
# HarmonyOS App
# DevEco Studio로 apps/harmonyos 디렉터리 열기
cd apps/flutter && flutter run -d android # Mobile

# TypeScript 검사
cd admin/public/web && npx vue-tsc --noEmit   # 오류 0개
```

사용 안내 → [docs/usage.ko.md](docs/usage.ko.md)
---

## 프로젝트 구조

```
ads-php/
├── service/                           # 사용자 측 비즈니스 서비스 (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 엔드포인트, 버전 라우팅)
│   │   │   ├── controller/v1/         # 17개 컨트롤러
│   │   │   ├── middleware/            # 15개 미들웨어
│   │   │   ├── config/route.php       # 라우트 정의
│   │   │   └── route_helpers.php      # versioned() 헬퍼 함수
│   │   ├── ads-platform/              # 플랫폼 어댑터 코어
│   │   │   ├── adapter/               # 29개 플랫폼 어댑터
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL 마이그레이션 + 성능 인덱스
│   │   ├── ads-account/               # OAuth 계정 관리
│   │   ├── ads-task/                  # 예약 작업 스케줄링 (6 cron)
│   │   ├── ads-alert/                 # 경보 모니터링 엔진 + 예산 경보
│   │   ├── ads-report/                # 보고서 엔진 (CSV/Excel/PDF) + 기여도 엔진 + 집행 캘린더
│   │   ├── ads-tenant/                # 멀티 테넌트 관리
│   │   └── ads-storage/               # 스토리지 추상화 계층 (local/OSS/COS/S3) + CDN 프로바이더
│   ├── scripts/backfill-assets.php    # 기존 소재를 객체 스토리지로 백필
│   ├── support/                       # Erik Stack 유틸리티 클래스
│   │   ├── ControllerTrait.php        # 컨트롤러 공용 trait
│   │   ├── JwtService.php             # JWT 래퍼 클래스
│   │   ├── CacheService.php           # Redis 캐시 서비스
│   │   ├── ExceptionHandler.php       # API 예외 처리기
│   │   └── ApiResponse.php            # 통일 응답 형식
│   ├── config/                        # 전역 설정 (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit 테스트 (288 tests)
│   │   ├── Unit/                      # 단위 테스트 (Middleware, Task)
│   │   └── Integration/               # 통합 테스트 (Auth, Health)
│   └── start.php                      # 서비스 진입점
├── admin/                             # 독립 관리 백엔드 (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15개 Vue 페이지
│   │   │   ├── dashboard/             # 대시보드 (ECharts)
│   │   │   ├── campaign/              # 광고 캠페인
│   │   │   ├── adgroup/               # 광고 그룹
│   │   │   ├── creative/              # 광고 소재
│   │   │   ├── report/                # 보고서 분석 + 내보내기
│   │   │   ├── alert/                 # 경보 규칙 + 기록
│   │   │   ├── notification/          # 알림 센터
│   │   │   ├── bid/                   # 자동 입찰 규칙
│   │   │   └── system/                # 사용자 관리 + 감사 로그
│   │   ├── api/                       # 9개 API 클라이언트
│   │   ├── stores/                    # 4개 Pinia Store
│   │   └── components/                # 공유 컴포넌트 (ListPageLayout 등)
│   ├── app/                           # PHP 백엔드 (controller/middleware)
│   └── config/                        # Admin 설정
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12개 기능 페이지 + Shell 레이아웃
│   │       ├── config/menu_config.dart # 2단계 메뉴 설정
│   │       ├── router.dart            # GoRouter (ShellRoute + 라우트 가드)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client 준비 완료)
├── docker/                            # Docker & Nginx 설정
├── .github/workflows/                 # CI (문법→테스트→TS→Docker) + CD (빌드/푸시)
├── docs/                              # 설계 문서, 구현 계획, Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API 엔드포인트

> 전체 API 엔드포인트 정의는 [docs/api.md](docs/api.ko.md) 참조（요청/응답 예시, 오류 코드, 속도 제한 정책 포함）。
> hg/apidoc 온라인 문서: 서비스 시작 후 `http://127.0.0.1:8788/apidoc` 접속

## 데이터베이스

**네이밍 규칙**: 테이블 접두사 `ads_`, 기본 키 `BIGINT UNSIGNED PRIMARY KEY`（자동 증가 없음, Snowflake ID）, 엔진 InnoDB, 문자셋 utf8mb4

| 분류 | 테이블 이름 | 용도 |
|------|------|------|
| 기반 | `ads_tenants` | 멀티 테넌트 |
| 계정 | `ads_platform_accounts`, `ads_auth_tokens` | OAuth 플랫폼 계정 |
| 집행 | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | 광고 집행 계층 |
| 보고서 | `ads_report_metrics`, `ads_report_extras` | 통일 보고서 지표 |
| 소재 | `ads_assets` | 소재 라이브러리 |
| CDN | `ads_cdn_providers` | CDN 프로바이더 설정 (자격 증명 암호화) |
| 타겟팅 | `ads_targeting_templates` | 타겟팅 템플릿 |
| 기여도 | `ads_conversions`, `ads_attribution_results` | 전환 추적 + 기여도 결과 |
| 입찰 | `ads_bid_rules`, `ads_bid_logs` | 자동 입찰 규칙 + 이력 |
| 경보 | `ads_alert_rules`, `ads_alert_logs` | 경보 모니터링 |
| 알림 | `ads_notifications` | 사내 알림 |
| 시스템 | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | 동기화 오류, RBAC, 감사 |

---

## 예약 작업

| 작업 | 빈도 | 기능 |
|------|------|------|
| TokenRefreshTask | 55분마다 | 만료 OAuth Token 스캔, 자동 갱신 |
| DataSyncTask | 10분마다 | 각 플랫폼 캠페인+광고 그룹+소재+보고서 수집, 통일 테이블 저장, 캐시 삭제 |
| AlertCheckTask | 5분마다 | 활성 경보 규칙 순회, 임계값 평가, 푸시 트리거 |
| BidCheckTask | 10분마다 | 자동 입찰 규칙 순회, 지표 조회, 예산 조정/시작·중지 실행 |
| BudgetCheckTask | 15분마다 | 집행 중인 캠페인 순회, 일일 예산 소진 추적, 3단계 경보 (50/80/100%) |
| RetrySyncTask | 3분마다 | 실패한 동기화 작업 재시도 (최대 3회, 지수 백오프) |

---

## 테스트

```bash
cd service && ./vendor/bin/phpunit
# 288 테스트 / 862 어서션
```

**커버리지 범위**: 미들웨어 14개 · 플러그인 비즈니스 레이어 8개 (계정/알림/플랫폼/리포트/태스크/테넌트/스토리지) · 엔진 (Bid/Alert/Attribution/Report) · API 통합 테스트 (76 라우트) · UI E2E (18 페이지)

```bash
# TypeScript 검사
cd admin/public/web && npx vue-tsc --noEmit   # 오류 0개

# Dart 분석
cd apps/flutter && dart analyze   # 오류 0개
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): 자동 파이프라인 — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): 수동 트리거 — **Docker Buildx → GHCR 푸시 (service/admin/admin-php) → 배포 알림**

`.github/dependabot.yml`이 매주 Composer + npm + Docker 의존성을 자동 업데이트.

---

## Skills

`docs/skills/` — 11개 재사용 가능한 프로젝트 스킬:

| Skill | 설명 |
|------|------|
| `adapter-generator` | 새 광고 플랫폼 어댑터 생성 (14 메서드 템플릿) |
| `migration-generator` | SQL 마이그레이션 파일 생성 (ads_ 접두사 + BIGINT PK) |
| `erik-stack` | Erik Stack 8개 패키지 통합 사용 가이드 |
| `admin-page-generator` | Vue3 관리 백엔드 페이지 생성 |
| `api-endpoint` | RESTful API 엔드포인트 추가 |
| `tdd-workflow` | TDD 검증 흐름 (테스트→구현→문법→TypeScript→커밋) |
| `security-middleware` | 보안 미들웨어 계층 추가 (인터페이스 규약 + 등록 + 기존 체인 참고) |
| `version-split` | Lite/Standard/Full 3개 버전 분리 (작업 절차 + 설정 업데이트) |
| `cache-strategy` | 3단계 캐시 전략 (L1 메모리/L2 APCu/L3 Redis + TTL 권장) |
| `attribution-setup` | 플랫폼 간 기여도 엔진 (5개 모델 + API 호출 + 데이터 준비) |
| `high-concurrency` | 고동시성 8개 최적화 (읽기/쓰기 분리/커넥션 풀/메시지 큐/수평 확장/CDN) |


## 오픈소스는 쉽지 않습니다. 후원을 환영합니다

| WeChat | Alipay |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### 글로벌 송금 후원 (Global Transfer Donation)

**수취인 정보 (Beneficiary)**

| 필드 | 값 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**수취 은행 (Receiving Bank) — ZA Bank**

| 필드 | 값 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **해외송금 중계 은행 (필요 시, Correspondent Bank)**：이는 중계(거쳐가는) 은행 정보로, 수취 은행 정보가 아닙니다. 송금 은행에 필요 여부를 문의하세요.
>
> - **홍콩 달러, 위안화 및 미국 달러**: Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **기타 통화**: THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

### 암호화폐 후원 (Crypto Donation)

이 프로젝트가 도움이 되셨다면, QR 코드를 스캔하여 후원해 주세요. 감사합니다!

| 네트워크 (Network) | QR 코드 (QR Code) | 지갑 주소 (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="./coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](./coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="./coin/2.jpg" width="150" alt="Tron (TRC20)">](./coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="./coin/3.jpg" width="150" alt="Ethereum (ERC20)">](./coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="./coin/4.jpg" width="150" alt="Aptos">](./coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="./coin/5.jpg" width="150" alt="Plasma">](./coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="./coin/6.jpg" width="150" alt="Polygon POS">](./coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="./coin/7.jpg" width="150" alt="Solana">](./coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="./coin/8.jpg" width="150" alt="The Open Network (TON)">](./coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="./coin/9.jpg" width="150" alt="Arbitrum One">](./coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="./coin/10.jpg" width="150" alt="AVAX C-Chain">](./coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

---

## 라이선스

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
