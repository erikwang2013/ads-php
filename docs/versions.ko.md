# 버전 비교

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| 버전 | 라이선스 | 획득 방법 |
|------|------|----------|
| **간소화 버전 (Lite)** | 오픈소스 (MIT) | GitHub 공개 저장소 |
| **표준 버전 (Standard)** | 상업 라이선스 | erik@erik.xyz 문의 |
| **전체 버전 (Full)** | 상업 라이선스 | erik@erik.xyz 문의 |

---

## 기능 비교

### 기본 기능

| 기능 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 인증 (로그인/Token 갱신/현재 사용자) | ✅ | ✅ | ✅ |
| 플랫폼 관리 (29개 플랫폼 목록 + OAuth) | ✅ | ✅ | ✅ |
| 계정 관리 (CRUD + 동기화) | ✅ | ✅ | ✅ |
| 광고 캠페인 (CRUD + 시작/중지 + 일괄) | ✅ | ✅ | ✅ |
| 보고서 (대시보드 + 커스텀 + CSV/Excel/PDF 내보내기) | ✅ | ✅ | ✅ |
| 헬스 체크 + API 문서 + 캡차 | ✅ | ✅ | ✅ |
| 데이터 동기화 (Campaign + Report) | ✅ | ✅ | ✅ |

### 집행 관리

| 기능 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 광고 그룹 (CRUD + 시작/중지) | — | ✅ | ✅ |
| 광고 소재 (목록 + 상세) | — | ✅ | ✅ |
| 광고 그룹/소재 데이터 동기화 | — | ✅ | ✅ |

### 모니터링과 알림

| 기능 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 경보 규칙 엔진 (7개 지표/4개 조건/3개 범위) | — | ✅ | ✅ |
| 경보 기록 + 확인 + 미확인 수 | — | ✅ | ✅ |
| 알림 센터 (목록/읽음/전체 읽음) | — | ✅ | ✅ |

### 고급 기능

| 기능 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 자동 입찰 규칙 엔진 (3개 작업/쿨다운) | — | — | ✅ |
| 타겟팅 템플릿 (공용 JSON Schema) | — | — | ✅ |
| 광고 소재 라이브러리 (업로드/갤러리/미리보기) | — | — | ✅ |
| 예산 경보 (3단계 경보 50/80/100%) | — | — | ✅ |
| 집행 캘린더 (Gantt 시각화) | — | — | ✅ |
| 플랫폼 간 기여도 (5개 모델/30일 소급) | — | — | ✅ |

---

## 보안 방어 비교

| 방어 항목 | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| CORS 화이트리스트 | ✅ | ✅ | ✅ |
| 보안 응답 헤더 (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| 버전 라우팅 (/api/v1) | ✅ | ✅ | ✅ |
| API 속도 제한 (슬라이딩 윈도우) | ✅ | ✅ | ✅ |
| SQL 주입 검출 (패턴 매칭) | ✅ | ✅ | ✅ |
| 입력 필터링 (strip_tags + trim) | ✅ | ✅ | ✅ |
| 전송 암복호화 (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer 인증 | ✅ | ✅ | ✅ |
| XSS 공격 검출 (11개 패턴) | — | ✅ | ✅ |
| 경로 탐색 검출 (7개 패턴) | — | ✅ | ✅ |
| Header 주입 검출 | — | ✅ | ✅ |
| Body 크기 제한 (10 MiB) | — | ✅ | ✅ |
| Content-Type 화이트리스트 | — | ✅ | ✅ |
| 클라이언트 출처 식별 (8개 단말) | — | ✅ | ✅ |
| 로그인 스로틀 (5회→15분) | — | ✅ | ✅ |
| 응답 시간 모니터링 (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer 검증 | — | — | ✅ |
| 리플레이 공격 방어 (Nonce+Timestamp) | — | — | ✅ |
| 동시 세션 제한 (최대 3개) | — | — | ✅ |
| CSRF Token (Admin 측) | — | — | ✅ |
| SSRF 방어 (OAuth 화이트리스트) | — | — | ✅ |
| 로그 데이터 마스킹 | — | — | ✅ |
| JWT IP/UA 바인딩 | — | — | ✅ |

---

## 미들웨어 체인 비교

### Service 측

| Lite (7계층) | Standard (11계층) | Full (15계층) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Admin 측

| Lite (1계층) | Standard (4계층) | Full (5계층) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## 예약 작업 비교

| 작업 | 빈도 | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (Campaign+Report만) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## 데이터베이스 테이블 비교

| 분류 | 테이블 이름 | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| 기반 | ads_tenants | ✅ | ✅ | ✅ |
| 계정 | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| 집행 | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| 경보 | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| 알림 | ads_notifications | — | ✅ | ✅ |
| 입찰 | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| 타겟팅 | ads_targeting_templates | — | — | ✅ |
| 소재 | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| 기여도 | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| 시스템 | ads_sync_errors | ✅ | ✅ | ✅ |
| 관리 | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **합계** | | **8** | **13** | **19** |

---

## 프론트엔드 페이지 비교

### Vue Admin SPA

| 페이지 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 로그인 | ✅ | ✅ | ✅ |
| 대시보드 | ✅ | ✅ | ✅ |
| 계정 목록 + 바인딩 | ✅ | ✅ | ✅ |
| 광고 캠페인 | ✅ | ✅ | ✅ |
| 보고서 내보내기 | ✅ | ✅ | ✅ |
| 사용자 관리 | ✅ | ✅ | ✅ |
| 감사 로그 | ✅ | ✅ | ✅ |
| 광고 그룹 | — | ✅ | ✅ |
| 광고 소재 | — | ✅ | ✅ |
| 보고서 분석 (ECharts) | — | ✅ | ✅ |
| 경보 규칙 | — | ✅ | ✅ |
| 경보 기록 | — | ✅ | ✅ |
| 알림 센터 | — | ✅ | ✅ |
| 자동 입찰 | — | — | ✅ |
| 소재 라이브러리 | — | — | ✅ |
| CDN 프로바이더 | — | — | ✅ |
| 집행 캘린더 | — | — | ✅ |
| 기여도 분석 | — | — | ✅ |
| **합계** | **7** | **13** | **18** |

### Flutter

| 페이지 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 로그인 | ✅ | ✅ | ✅ |
| 대시보드 | ✅ | ✅ | ✅ |
| 광고 캠페인 (목록+상세) | ✅ | ✅ | ✅ |
| 데이터 보고서 | ✅ | ✅ | ✅ |
| 플랫폼 계정 | ✅ | ✅ | ✅ |
| 경보 관리 | ✅ | ✅ | ✅ |
| 광고 그룹 | — | ✅ | ✅ |
| 광고 소재 | — | ✅ | ✅ |
| 보고서 분석 | — | ✅ | ✅ |
| 알림 센터 | — | ✅ | ✅ |
| 자동 입찰 | — | — | ✅ |
| **합계** | **6** | **10** | **11** |

---

## API 엔드포인트 비교

| 모듈 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 시스템 (health/ping/docs/captcha) | 6 | 6 | 6 |
| 인증 (login/me/refresh) | 3 | 3 | 3 |
| 플랫폼 (list/oauthUrl/callback) | 3 | 3 | 3 |
| 계정 (index/show/destroy/sync) | 4 | 4 | 4 |
| 광고 캠페인 (CRUD/toggle/batch) | 6 | 6 | 6 |
| 광고 그룹 (CRUD/toggle) | — | 5 | 5 |
| 소재 (index/show) | — | 2 | 2 |
| 보고서 (summary/custom/export×2) | 4 | 4 | 4 |
| 보고서 (calendar/budget/attribution/models) | — | — | 4 |
| 경보 (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| 알림 (index/unread/read/readAll) | — | 4 | 4 |
| 자동 입찰 (CRUD + logs) | — | — | 5 |
| 타겟팅 템플릿 (CRUD) | — | — | 5 |
| 소재 라이브러리 (index/upload/show/destroy/presign/register) | — | — | 6 |
| CDN 프로바이더 (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **합계** | **26** | **44** | **70** |

---

## 기술 스택

3개 버전이 공통 기술 스택 공유:

| 계층 | 기술 |
|----|------|
| 백엔드 프레임워크 | webman v2, PHP 8.2+ |
| 데이터베이스 | MySQL 8.0 (InnoDB, utf8mb4) |
| 캐시 | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| 인증 | erikwang2013/jwt-webman |
| ID 생성 | erikwang2013/snowflake-php |
| ID 인코딩 | erikwang2013/hashids |
| 프론트엔드 | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| 배포 | Docker + Nginx + Docker Compose |

---

## 업그레이드 경로

```
Lite (오픈소스)
  │
  ├─→ Standard로 업그레이드 (erik@erik.xyz 문의)
  │     │
  │     └─→ 추가: 광고 그룹/소재 관리, 경보 엔진, 알림 센터,
  │              AttackGuard/XSS/경로 탐색/로그인 스로틀/응답 시간 모니터링
  │
  └─→ Full로 업그레이드 (erik@erik.xyz 문의)
        │
        └─→ 추가: Standard 전체 + 자동 입찰, 타겟팅 템플릿, 소재 라이브러리,
                  예산 경보, 집행 캘린더, 플랫폼 간 기여도, 리플레이 방어/동시성 제한/CSRF/SSRF
```
