# 기능 설계 문서

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> 모든 API 인터페이스 정의（요청/응답/파라미터）는 [api.md](api.ko.md) 참조.

---

## 모듈 총람

| # | 모듈 | 컨트롤러/서비스 | API 라우트 수 | Vue 페이지 |
|---|------|--------|-----------|----------|
| 1 | 인증/권한 | AuthController | 3 | LoginPage |
| 2 | 플랫폼 관리 | PlatformController | 3 | — |
| 3 | 계정 관리 | AccountController | 5 | AccountList, AccountBind |
| 4 | 광고 캠페인 | CampaignController | 6 | CampaignList |
| 5 | 광고 그룹 | AdGroupController | 5 | AdGroupList |
| 6 | 광고 소재 | CreativeController | 2 | CreativeList |
| 7 | 데이터 보고서 | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | 경보 모니터링 | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | 알림 센터 | NotificationController | 4 | NotificationList |
| 10 | 자동 입찰 | BidRuleController | 5 | BidRuleList |
| 11 | 타겟팅 템플릿 | TargetingTemplateController | 5 | — |
| 12 | 시스템 관리 | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | 데이터 동기화 | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | 소재 라이브러리 | AssetController | 4 | AssetGallery |
| 15 | 예산 경보 | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | 집행 캘린더 | CalendarService | 1 | CampaignCalendar |
| 17 | 플랫폼 간 기여도 | AttributionEngine | 2 | AttributionReport |
| 18 | 헬스 체크 | HealthController | 2 | — |
| 19 | 캡차 | CaptchaController | 2 | — |
| 20 | API 문서 | DocController | 1 | — |

**합계**: 20 모듈, 65+ 라우트, 18 Vue 페이지

---

## 모듈 1: 인증/권한

- 캡차 검사 (선택 사항)
- `admin_users` 테이블 조회
- bcrypt `password_verify()` 검증
- JWT Token 생성 (24h TTL)
- 기존 Token 자동 블랙리스트 등록
- Token에서 `uid` 추출하여 사용자 정보 조회

인터페이스: 로그인 / Token 갱신 / 현재 사용자 → [api.md 모듈 2](api.ko.md#模块-2-认证)

---

## 모듈 2-3: 플랫폼과 계정 관리

- 플랫폼 목록 1시간 캐시 (Redis), Season 국기 emoji 통합
- OAuth 흐름: 랜덤 state 생성 → 인증 URL 구성 → 콜백 처리 → Token 저장
- 계정 목록/상세 5분 캐시

인터페이스: 플랫폼 목록 / OAuth / 계정 CRUD + 동기화 → [api.md 모듈 3](api.ko.md#模块-3-平台--账户)

---

## 모듈 4-6: 광고 집행 계층

### 데이터 구조

```
Campaign (광고 캠페인)
  ├── AdGroup (광고 그룹) × N
  │     └── Creative (소재) × N
  └── ReportMetrics (보고서 지표)
```

- 캠페인 생성은 플랫폼 어댑터를 통해 + 로컬 저장
- 플랫폼/상태/키워드 필터 지원, 목록에 오늘 집계 포함
- 광고 그룹 생성 시 `targeting_template_id`로 타겟팅 템플릿 로드 지원

인터페이스: 캠페인 / 광고 그룹 / 소재 → [api.md 모듈 4-6](api.ko.md#模块-4-广告计划)

---

## 모듈 7: 데이터 보고서

- 대시보드 집계 5분 캐시: 8개 KPI 지표 카드 + 일간 추세 꺾은선 그래프 + 플랫폼 막대 그래프
- 커스텀 보고서 차원: date, platform, campaign
- 지표: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- 내보내기 형식: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML 인쇄)

인터페이스: 집계 / 커스텀 / 내보내기 → [api.md 모듈 7](api.ko.md#模块-7-报表)

---

## 모듈 8: 경보 모니터링

### AlertEngine 평가 흐름

```
enabled=1 규칙 순회
  → erik_report_metrics 조회 (오늘 데이터, scope 기준 필터)
  → compare(metric_value, threshold, condition)
  → 중복 검사 (check_interval 내 이미 트리거됨 → 건너뜀)
  → AlertLog 생성 (status=triggered)
  → NotificationService.send()
```

### 알림 채널

| 채널 | 상태 | 구현 |
|------|------|------|
| web | ✅ | erik_notifications에 저장 |
| email | 자리 표시 | echo 스텁 |
| sms | 자리 표시 | echo 스텁 |
| Redis pub/sub | ✅ | `alert:new` 채널 JSON 푸시 |

인터페이스: 규칙 CRUD / 경보 기록 / 확인 / 미확인 수 → [api.md 모듈 8](api.ko.md#模块-8-告警)

---

## 모듈 9: 알림 센터

- 프론트엔드 Pinia store 30초 폴링
- 사이드바 종 아이콘 + 미확인 숫자 배지

인터페이스: 목록 / 미확인 수 / 읽음 표시 / 전체 읽음 → [api.md 모듈 9](api.ko.md#模块-9-通知)

---

## 모듈 10: 자동 입찰 엔진

### BidEngine 평가 흐름

```
enabled=1 규칙 순회
  → erik_report_metrics 조회 (오늘 데이터, scope 기준 필터)
  → compare(metric_value, threshold, condition)
  → 쿨다운 검사 (cooldown_minutes 내 작업 이력 확인)
  → 작업 실행:
    - adjust_budget: 새 예산 = current + adjust_step, [budget_min, budget_max] 범위 제한
    - toggle_pause: 캠페인 일시 중지
    - toggle_enable: 캠페인 활성화
  → AdapterRegistry → PlatformAdapter를 통해 플랫폼 API 호출
  → 로컬 DB 업데이트 + BidLog 저장
```

### 규칙 필드

| 필드 | 타입 | 설명 |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | 모니터링 지표 |
| condition | gt/gte/lt/lte | 트리거 조건 |
| threshold | DECIMAL(12,2) | 임계값 |
| scope | tenant/platform/campaign | 적용 범위 |
| action_type | adjust_budget/toggle_pause/toggle_enable | 작업 |
| adjust_step | INT (분) | 예산 조정 단위 (양수=증가, 음수=감소) |
| budget_min, budget_max | BIGINT | 예산 경계 |
| cooldown_minutes | INT | 쿨다운 기간 |

인터페이스: 규칙 CRUD / 입찰 이력 → [api.md 모듈 10](api.ko.md#模块-10-自动出价)

---

## 모듈 11: 타겟팅 템플릿

### 광고 그룹에 통합

```
POST /api/ad-groups 에서 targeting_template_id 지원
→ 템플릿 targeting JSON 로드
→ 요청의 targeting 오버라이드 병합
→ 플랫폼 어댑터에 전달
```

### 공용 JSON Schema

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

인터페이스: 템플릿 CRUD → [api.md 모듈 11](api.ko.md#模块-11-定向模板)

---

## 모듈 12: 시스템 관리 (Admin)

- 사용자 목록 ID hashids 인코딩
- 사용자 생성 시 bcrypt 비밀번호 해시
- 사용자 비활성화는 소프트 비활성화 (status=0)

감사 로그 필드: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

인터페이스: 사용자 관리 / 감사 로그 / 역할 → [api.md Admin 엔드포인트](api.ko.md#admin-端点端口-8789)

---

## 모듈 13: 데이터 동기화

### DataSyncTask 흐름 (10분마다)

```
sync_enabled=1 계정 순회
  → 플랫폼 어댑터 획득
  → Campaigns 동기화 (fetchCampaigns → updateOrInsert)
  → AdGroups 동기화 (fetchAdGroups → 캠페인별 순회)
  → Creatives 동기화 (fetchCreatives → ad_group별 순회)
  → Reports 동기화 (fetchReports → 과거 2일 daily, 9개 지표)
  → Dashboard 캐시 삭제
  → last_sync_at 업데이트
```

---

## 응답 형식

### 성공
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### 페이징
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### 오류
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## 모듈 14: 광고 소재 라이브러리

- 지원 타입: image/jpeg, image/png, image/gif, image/webp, video/mp4
- 파일 저장: `public/uploads/assets/`
- 프론트엔드: 그리드 갤러리 + 드래그 앤 드롭 업로드 + 이미지 미리보기 + 영상 재생 + URL 복사

인터페이스: 업로드 / 목록 / 상세 / 삭제 → [api.md 모듈 12](api.ko.md#模块-12-素材库)

---

## 모듈 15: 예산 경보

- 3단계 경보: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask 15분마다 실행
- 중복 방지: 같은 캠페인 같은 레벨은 하루에 한 번만 알림
- `erik_notifications` 테이블에 저장

인터페이스: 예산 경보 → [api.md 모듈 7](api.ko.md#模块-7-报表)

---

## 모듈 16: 집행 캘린더

- 날짜별 campaign 일정 집계
- 프론트엔드 Gantt 차트: x축 날짜, y축 캠페인, 플랫폼별 색상 구분
- 월/주 뷰 전환 지원

인터페이스: 집행 캘린더 → [api.md 모듈 7](api.ko.md#模块-7-报表)

---

## 모듈 17: 플랫폼 간 기여도

### 기여도 모델

| 모델 | 알고리즘 |
|------|------|
| first_touch | 첫 번째 터치포인트 100% |
| last_touch | 마지막 터치포인트 100% |
| linear | 모든 터치포인트 균등 분배 (1/N) |
| time_decay | e^(-λ×Δt), 7일 반감기 |
| position_based | 처음 40% + 마지막 40% + 중간 20% |

- 소급 창: 30일
- 터치포인트 출처: `erik_report_metrics` (클릭 > 0)
- 결과는 `erik_attribution_results`에 저장
- 프론트엔드: AttributionReport.vue 모델 전환 + 통계 카드 + ECharts 막대 그래프 + 상세 테이블

### 데이터 테이블

| 테이블 | 필드 |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

인터페이스: 기여도 분석 / 모델 목록 → [api.md 모듈 7](api.ko.md#模块-7-报表)

### 헬스 체크
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```
