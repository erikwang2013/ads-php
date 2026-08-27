# Phase 10: 심화와 상업화 구현 계획

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**목표:** Phase 7-9의 계약과 다중 채널 기반 위에 동기화 상태 시각화, 전환 데이터 루프 클로즈, 모바일 CI 패키징, 멀티 테넌트 SaaS 할당량 4가지 심화 능력을 구축합니다.

**출처:** Phase 7 팀 감사 추론 방향(researcher: ES/읽기-쓰기 분리/큐 구축, Flutter/鸿蒙 CI, 29 플랫폼 실제 연동, SaaS 과금 할당량, 전환 데이터 루프 클로즈, 동기화 상태 시각화, AI 입찰)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## 현황(확인 완료)

| 후보 하위 항목 | 현황 |
|---|---|
| 동기화 상태 시각화 | `ads_sync_errors` 테이블 + `RetrySyncTask`(재시도 3회, 백오프 5^n분) 이미 존재; **동기화 실패율과 지연을 보여주는 프론트엔드 페이지/API 없음** |
| 전환 데이터 루프 클로즈 | `ads_conversions` + `ads_attribution_results` 테이블 존재, 기여도 엔진 구현 완료; **전환 데이터 수집 진입점 없음**(콜백/추적 API) |
| 모바일 CI | `ci.yml`은 PHP 문법→PHPUnit→vue-tsc→Docker만; **Flutter/HarmonyOS 빌드 패키징 없음** |
| 멀티 테넌트 SaaS | `ads_tenants` 테이블 + TenantIdentify 미들웨어 존재; **과금/할당량/사용량 통계 없음** |
| ES 구축 | scout.php 구성 + webman-scout 의존성 도입 완료; **docker-compose에 ES 서비스 없음** |
| 29 플랫폼 실제 연동 | 29 어댑터 코드 완비; **샌드박스/자격 증명 연동 기록 없음**(외부 자격 증명 필요, 수동 항목으로 표시) |

## Task 1: 동기화 상태 시각화

### Files:
- 수정: `service/plugin/ads-api/controller/v1/DashboardController.php` 또는 신규 `service/plugin/ads-api/controller/v1/SyncController.php` + route
- 생성: `admin/public/web/src/api/sync.ts`
- 생성: `admin/public/web/src/views/sync/SyncStatus.vue`(또는 시스템 페이지에 통합)

### 설계 요점
- 엔드포인트: `GET /api/sync/status`(계정 차원: last_sync_at, 성공률, 오늘 실패 수, pending 재시도 수) + `GET /api/sync/errors`(페이지네이션 오류 목록, last_error/retry_count/next_retry_at 포함)
- 프론트엔드: 동기화 상태 페이지(테이블 + 요약 카드), Full/Standard 버전 라인만
- 데이터 소스: ads_platform_accounts(last_sync_at) + ads_sync_errors

## Task 2: 전환 데이터 수집 API

### Files:
- 수정: `service/plugin/ads-api/controller/v1/`(ConversionController + route 신규 추가)
- 생성: `service/plugin/ads-report/service/ConversionService.php`

### 설계 요점
- 엔드포인트: `POST /api/conversions`(비즈니스 측 전환 콜백: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions`(조회)
- 검증: campaign_id 존재, 금액 비음수, 시간 형식; ads_conversions에 기록
- 기여도 연동: 콜백 후 기여도 재계산 트리거 가능(또는 기존 AttributionEngine이 정기/수동 재계산한다는 설명)
- 프론트엔드: 기여도 보고서 페이지에 "전환 콜백" 설명/데모 추가(선택)

## Task 3: 모바일 CI 패키징

### Files:
- 수정: `.github/workflows/ci.yml`(신규 job: Flutter build(web + linux 또는 apk) + HarmonyOS 정적 검사)

### 설계 요점
- Flutter: `flutter pub get && flutter analyze && flutter build web`(또는 apk, 저장소 현황에 맞는 빌드 대상 선택; flutter 환경 제약 시 dart analyze 사용)
- HarmonyOS: 표준 Linux CI 툴체인 없음, 정적 검사 설명 또는 건너뛰기(표기)
- 기존 php-tests job과 병렬 실행, 메인 흐름 비차단

## Task 4: 멀티 테넌트 SaaS 할당량(MVP)

### Files:
- 수정: `service/plugin/ads-tenant/`(QuotaService 신규 추가)
- 수정: `service/plugin/ads-api/config/route.php` + controller

### 설계 요점
- 데이터: ads_tenants에 quota 필드 추가 또는 신규 테이블 ads_tenant_quotas(plan/account_limit/campaign_limit/sync_quota)
- 검증 지점: 계정 바인딩 수, 계획 생성 수, 일일 동기화 횟수(AccountController/CampaignController/DataSyncTask 진입점에서 검사)
- 엔드포인트: `GET /api/tenant/quota`(사용량 + 할당량)
- 프론트엔드: 시스템 페이지에 할당량 사용량 표시(선택, MVP는 API만 가능)
- 버전 라인: quota 기본값을 lite/standard/full별로 구분(config 상수)

## 수용(Task별)
- [ ] Task 1: sync API 엔드포인트 사용 가능, 프론트엔드 페이지 표시, 테스트 커버리지
- [ ] Task 2: conversions 콜백 API 쓰기/조회 가능, 검증 적용, 테스트 커버리지
- [ ] Task 3: CI 신규 job 통과(또는 건너뛴 항목 명시 표기)
- [ ] Task 4: quota API가 올바른 값 반환, 초과 차단 적용, 테스트 커버리지
- [ ] 전체: `php vendor/bin/phpunit --no-coverage` 전부 통과, vue-tsc 통과

## 이번 범위 제외(외부 리소스 필요)
- 29 플랫폼 실제 연동(각 플랫폼 자격 증명/샌드박스 필요)
- ES 서비스 구축(docker-compose에 ES 서비스와 인덱스 초기화 추가 필요)
- AI 입찰 제안(모델/데이터 준비)
