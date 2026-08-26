# Phase 7: 크로스 플랫폼 계약 수정 구현 계획

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **상태 업데이트(2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ 전부 완료, tester 회귀 검증 통과(35 tests OK, 계약 교차 검증에서 유령 엔드포인트 없음, Phase 7 수용 가능).

**목표:** 팀 감사에서 발견된 크로스 플랫폼 API 계약 문제를 수정합니다: Flutter 유령 엔드포인트 3개(404), Admin `admin.ts` 이중 접두사 버그, `/system/info` 라우트 없음, ServiceProxy 미배선, 문서 구식화. 3개 단말(Admin/Flutter/HarmonyOS)의 service API 일관된 소비를 복구합니다.

**출처:** 2026-08-07 팀 병렬 감사(backend-dev 라우트 점검 61개 엔드포인트, vue-dev Admin 호출 점검 50개 호출 지점, mobile-dev 모바일 단말 점검, researcher 구현/계획 점검 교차 비교)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Flutter 유령 엔드포인트 수정 (🔴 최우선)

### 배경
Flutter 3개 페이지가 service에 존재하지 않는 라우트를 호출하여 전부 404:

| Flutter 호출 | service 실제 라우트 | 수정 방안 |
|---|---|---|
| `GET /dashboard` | 없음(대시보드 집계는 `/reports/summary`) | `GET /reports/summary`로 변경 |
| `GET /alerts` | 없음(경보는 `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | `GET /alerts/logs`로 변경(경보 목록 의미) |
| `GET /reports` | 없음(보고서는 `/reports/summary`, `/reports/custom`) | `GET /reports/custom`으로 변경(날짜/차원/지표 파라미터, ReportBuilder::buildCustom 일치) |

### Files:
- 수정: `apps/flutter/lib/features/dashboard/dashboard_page.dart`(`/dashboard` → `/reports/summary` ×2 구간, 응답 구조 `data.overview`/`by_platform`/`daily`에 맞춤) ✅
- 수정: `apps/flutter/lib/features/alert/alert_page.dart`(`/alerts` → `/alerts/logs`, 페이지네이션 구조 `data.list`에 맞춤, AlertLog 필드 rule_name/metric/current_value/condition/threshold) ✅
- 수정: `apps/flutter/lib/features/report/report_page.dart`(`/reports` → `/reports/custom`, 파라미터 date_start/date_end/dimensions[]/metrics[], `data.list` 파싱, 필드 cost) ✅
- 검증: 응답 필드가 `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php`의 실제 반환과 일치 ✅

### 수용
- [x] 세 곳 경로 수정 완료, 쿼리 파라미터 유지(report 페이지의 날짜 파라미터 → date_start/date_end + dimensions/metrics) ✅
- [x] 응답 파싱이 백엔드 실제 JSON 구조와 정렬(overview / paginated list / custom list) ✅
- [x] 수정 후 `flutter analyze` 오류 없음 — 본 환경은 Flutter SDK 캐시가 읽기 전용이라 실행 불가, SDK 내장 `dart analyze`로 전체 프로젝트 **0 errors**(기존 경고 15개는 모두 수정 전부터 존재, 이번에 신규 문제 미발생) ✅

---

## Task 2: Admin `admin.ts` 이중 접두사 버그 수정

### 배경
- `admin/public/web/src/api/admin.ts` 경로가 `/api/admin/...`로 되어 있는데, axios baseURL이 이미 `/api`(`src/api/index.ts`)라 실제로 `/api/api/admin/...`로 조합되어 UserManage.vue / AuditLog.vue의 5개 호출이 404일 가능성 높음.
- **심층 아키텍처 문제(vue-dev 최종 보고서 확인)**: admin 백엔드(8789)가 자체 로컬 라우트 12개(`/api/admin/login`, `me`, `logout`, `users` CRUD, `roles`, `audit-logs`, `/api/install/*`)를 제공하지만:
  - `docker/nginx/admin.conf`의 `location /api/` **전체**가 `service_api`(php:8788)로 proxy_pass;
  - `upstream admin_backend`(admin-php:8789)가 정의되어 있으나 **어떤 location도 이를 참조하지 않음** → 프로덕션 환경에서 `/api/admin/*`은 절대 8789에 도달 불가;
  - Vite dev 프록시 역시 `/api` 전체를 8788로 지정.
  - 결론: 이중 접두사를 고쳐도 `/api/admin/*`은 여전히 404 — admin 백엔드 로컬 라우트가 프로덕션 체인에 배선되지 않음.

### 결정 포인트(backend-dev + vue-dev + devops 확인 필요)
- 방안 A(권장): vue-dev가 `admin.ts` 경로를 상대 경로 `/admin/users`, `/admin/audit-logs`로 변경, 동시에 **devops가 Nginx에 `location /api/admin/` → `proxy_pass http://admin_backend` 추가**(`location /api/` 앞에 배치, 정확 접두사 우선 매칭)하여 admin 전용 라우트를 8789가 직접 서비스, 비즈니스 라우트는 여전히 8788 경유
- 방안 B: backend-dev가 service에 `/api/admin/*` 라우트 추가(Admin 단말 책임과 중복, 비권장)
- 방안 C: 비즈니스 쿼리도 ServiceProxy로 전환(배선 필요, 변경 최대, admin 단말 통일 인증이 필요할 때만 고려)

### Files:
- 수정: `admin/public/web/src/api/admin.ts`(`/api` 접두사 제거)
- 수정: `docker/nginx/admin.conf`(`location /api/admin/` → admin_backend upstream 신규 추가)
- 수정: `admin/public/web/vite.config.ts`(dev 프록시에 `/api/admin` → 8789 규칙 추가, `/api` 앞에 배치)
- 검증: `admin/config/route.php`의 admin 백엔드 라우트(/api/admin/users 등)가 프론트엔드 호출과 일치

### 수용
- [x] 프론트엔드 요청 경로가 실제 존재하는 백엔드 라우트와 일치(404 없음) — admin.ts 9개 메서드 전부 route.php와 대조 ✅, vue-tsc 통과
- [x] Nginx / Vite 모두 `/api/admin/*`을 8789로, `/api/*` 나머지를 8788로 정확히 분기 — Nginx에 `location /api/admin/` 추가, Vite에 `/api/admin` 프록시 추가(`/api` 앞) ✅
- [x] UserManage / AuditLog 페이지 기능 사용 가능 — 경로 정렬 완료(listRoles → `/admin/users/roles` 결정 포함) ✅

---

## Task 3: `/system/info` 라우트 없음 + ServiceProxy 결정

### 배경
- `SystemInfo.vue` / `stores/admin.ts`가 `GET /api/system/info` 호출, service에 이 라우트 없음(/health, /ping만 존재), 404가 try/catch로 삼켜짐
- `admin/app/controller/ServiceProxy.php`가 정의되어 있으나 저장소 전체에 활성 호출자 0개("정의만 하고 배선 안 함")

### 결정 포인트
- `/system/info`: 방안 A — 프론트엔드가 `/health` 호출로 변경(service에 이미 있음); 방안 B — backend-dev가 service에 `/api/system/info` 엔드포인트 추가(버전/환경 정보 반환, HarmonyOS/Flutter에도 유용, 권장)
- ServiceProxy: 방안 A — admin이 필요한 admin 전용 API(예: 감사 로그 포워딩)에 배선; 방안 B — 클래스 삭제 및 문서에 "Admin이 service에 직접 연결" 명시(현재 실제 아키텍처)

### 실행됨(2026-08-16)
- **`/system/info` → 방안 A(프론트엔드가 `/health` 호출로 변경)**: SystemInfo.vue를 네이티브 axios로 `GET /health` 호출하도록 수정, `checks.database === 'ok'` 판정; `/health` 라우트는 service 측에서 `/api` 접두사 없음, Vite에 `/health` 프록시 추가, Nginx에 기존 `location /health` 존재; `stores/admin.ts` 죽은 코드 동일하게 `/health`로 변경 ✅
- **ServiceProxy → 방안 B(유지 + 문서 설명)**: 클래스를 예약 인프라로 유지(`ServiceProxy::init()` 자체 초기화 무해), `admin/config/app.php` 주석을 "예약 인프라, 현재 활성 호출자 없음"으로 업데이트 ✅

### 수용
- [x] `/system/info` 결정 실행: 프론트엔드 호출 제거(/health로 변경), 404 유령 요청 없음 ✅
- [x] ServiceProxy 결정 실행: 클래스 유지, config 주석으로 현황 설명 ✅

---

## Task 4: 문서 보강과 용어 통일

### 배경
- README의 "14 컨트롤러 / 45+ 엔드포인트" 구식(실제 17 컨트롤러 / 61 엔드포인트)
- `docs/superpowers/plans/` 각 phase checkbox 미보강(코드 구현 완료 but 문서 미체크)
- HarmonyOS 상태 "UI 계획 중" 구식(실제 6 페이지 + ApiClient 준비 완료)
- install.html / InstallController 기본 `.../api/v1`과 config 기본 `/api`(X-API-Version 헤더) 불일치
- CacheService 주석이 2단계 캐시라고 하나 실제 3단계(L1 메모리 / APCu / Redis)

### Files:
- 수정: `README.md` / `README.en.md`(컨트롤러 수, 엔드포인트 수, HarmonyOS 상태, 캐시 계층)
- 수정: `admin/public/install.html` / `admin/app/controller/InstallController.php`(버전 접두사 용어 통일)
- 수정: `service/support/CacheService.php`(주석 정정)
- 선택: `docs/superpowers/plans/*.md` checkbox 보강

### 실행됨(2026-08-16)
- README.md / README.en.md: 17 컨트롤러 / 61 엔드포인트 / HarmonyOS 6 페이지 / 19 Vue 페이지 / SPA 직접 연결 용어 전부 업데이트 ✅
- install.html / InstallController: `/api/v1` 기본값 → `/api`(X-API-Version 헤더 메커니즘) ✅
- phase 계획 8부 checkbox 전부 보강 ✅(phase7 제외, 대기 중)

### 수용
- [x] README 데이터가 코드와 일치(17 컨트롤러 / 61 엔드포인트 / HarmonyOS 6 페이지) ✅
- [x] 설치 마법사 버전 접두사와 X-API-Version 메커니즘 일치 ✅

---

## 후속 단계 계획(Phase 8-10, 본 계획 외)

| Phase | 내용 | 상태 |
|---|---|---|
| Phase 8 | 경보 다중 채널 구축: ads-alert에 channel/(Email SMTP, Webhook, SMS 게이트웨이 자리 표시) 추가 — Phase 5 잔여 공백 보완 | 대기 중 |
| Phase 9 | HarmonyOS 실제 연동: 6 페이지를 ApiClient에 연결(현재 0개 실제 호출, 전부 시뮬레이션 데이터) | 대기 중 |
| Phase 10 | 심화와 상업화: 29 플랫폼 실제 연동, 동기화 상태 시각화, 전환 데이터 루프 클로즈, Flutter/HarmonyOS CI 패키징, 멀티 테넌트 SaaS 할당량 | 대기 중 |
