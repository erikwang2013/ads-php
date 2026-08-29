# API 인터페이스 문서

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **hg/apidoc 온라인 문서**: 서비스 시작 후 `http://127.0.0.1:8788/apidoc` 접속（Service + Admin 이중 앱 전환）  
> 설정 파일: `service/config/plugin/hg/apidoc/app.php`

---

## 공통 규약

### Base URL

```
http://your-domain.com/api
```

### 필수 Headers

| Header | 값 | 설명 |
|--------|----|------|
| `X-API-Version` | `v1` | API 버전 번호（필수, URL 경로에 나타나지 않음） |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | 작업 출처 단말（필수） |
| `Authorization` | `Bearer <token>` | JWT 인증 토큰（로그인/플랫폼 목록/헬스 체크 외 필수） |

### 리플레이 방지 Header（비브라우저 측）

| Header | 설명 |
|--------|------|
| `X-Nonce` | 랜덤 문자열（요청마다 고유） |
| `X-Timestamp` | Unix 초 단위 타임스탬프（±5분 창） |

### 선택 Headers

| Header | 설명 |
|--------|------|
| `X-Tenant-Id` | 테넌트 ID（멀티 테넌트 모드） |
| `X-Encrypted` | `1` = 요청 본문 복호화 필요, 응답 본문 암호화 필요 |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| 값 | 설명 |
|----|------|
| `application/json` | JSON 요청 본문（권장） |
| `application/x-www-form-urlencoded` | 폼 요청 |
| `multipart/form-data` | 파일 업로드 |

### 응답 형식

**성공**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**페이징**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**오류**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**헬스 체크**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP 상태 코드

| 상태 코드 | 의미 |
|--------|------|
| 200 | 성공 |
| 204 | OPTIONS 사전 검사 성공 |
| 400 | 요청 파라미터 오류, 지원하지 않는 API 버전 |
| 401 | 미인증, Token 만료, Token IP/UA 불일치 |
| 403 | 접근 금지（XSS/경로 탐색/CSRF/SQL 주입/Origin 불일치） |
| 404 | 리소스 없음 |
| 429 | 요청 과다（속도 제한/로그인 스로틀/동시 세션 제한） |
| 500 | 서버 오류 |
| 503 | 서비스 다운그레이드（DB 또는 Redis 사용 불가） |

### 페이징 파라미터

| 파라미터 | 기본값 | 최대값 | 설명 |
|------|--------|--------|------|
| `page` | 1 | — | 페이지 번호 |
| `per_page` | 20 | 100 | 페이지당 항목 수（초과 시 자동 절사） |
| `sort` | `id` | — | 정렬 필드（화이트리스트 내 필수） |

### 캐시 전략

| 엔드포인트 | TTL | 계층 |
|------|-----|-----|
| `/api/platforms` | 1시간 | L1 메모리 → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5분 | 위와 동일 |
| `/api/reports/summary` | 5분 | 위와 동일 |
| `/api/alerts/rules` | 2분 | 위와 동일 |
| `/api/alerts/unread-count` | 30초 | 위와 동일 |

---

## 모듈 1: 시스템

### GET /health — 헬스 체크

```
GET /health
```

**응답**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) 또는 `degraded` (503)
- 인증 요구 없음, 버전 라우팅 미적용

---

### GET /ping — 응답 확인

```
GET /ping
```

**응답**: `{ "pong": true }`

---

### GET /docs — API 문서

```
GET /docs
```

HTML 형식의 API 문서 페이지 반환（인증 불필요）.

---

### GET /api/captcha/generate — 캡차 생성

인증 불필요.

**응답**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- token 유효 기간 5분
- 오프셋 허용 오차 5px

---

### POST /api/captcha/verify — 캡차 검증

인증 불필요.

**요청**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**응답**: `{ "code": 0, "message": "验证通过" }`

---

## 모듈 2: 인증

### POST /api/auth/login — 로그인

인증 불필요.

**요청**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**응답**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- JWT Token 유효 기간 24시간
- Token에 IP + User-Agent hash 내장
- 5회 실패 → Redis 15분 잠금

---

### GET /api/auth/me — 현재 사용자

**요청 헤더**: `Authorization: Bearer <token>`

**응답**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/auth/refresh — Token 갱신

**요청 헤더**: `Authorization: Bearer <old_token>`

**응답**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- 기존 Token 자동 블랙리스트 등록
- 사용자당 최대 3개 활성 Token

---

## 모듈 3: 플랫폼 & 계정

### GET /api/platforms — 플랫폼 목록

인증 불필요. 1시간 캐시.

**응답**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/platforms/:code/oauth-url — OAuth 인증 URL

**파라미터**: `?redirect_uri=https://your-domain.com/callback`

**응답**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri`는 SSRF 화이트리스트 검증을 통과해야 함（`OAUTH_ALLOWED_REDIRECTS` 환경 변수）

---

### POST /api/platforms/:code/callback — OAuth 콜백

**요청**: `{ "state": "...", "code": "..." }`

**응답**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — 계정 목록

5분 캐시.

**파라미터**:

| 파라미터 | 설명 |
|------|------|
| `platform` | 플랫폼 코드 필터 |
| `page` | 페이지 번호 |
| `per_page` | 페이지당 항목 수 |

**응답**: 페이징 형식, list의 각 항목에 `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at` 포함

---

### GET /api/accounts/:id — 계정 상세

5분 캐시.

---

### DELETE /api/accounts/:id — 계정 해제

---

### POST /api/accounts/:id/sync — 수동 동기화

---

## 모듈 4: 광고 캠페인

### GET /api/campaigns — 캠페인 목록

**파라미터**:

| 파라미터 | 설명 | 선택 값 |
|------|------|--------|
| `platform` | 플랫폼 필터 | juliang, meta, google... |
| `status` | 상태 필터 | enabled, paused |
| `keyword` | 이름 검색 | 임의 텍스트 |
| `sort` | 정렬 필드 | id, name, platform, daily_budget, status, created_at |
| `page` | 페이지 번호 | — |
| `per_page` | 페이지당 항목 수 | ≤100 |

**응답**: 페이징 형식 + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — 캠페인 생성

**요청**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**응답**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` 단위: 분（20000 = ¥200.00）

---

### GET /api/campaigns/:id — 캠페인 상세

**응답**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — 캠페인 업데이트

**요청**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — 캠페인 시작/중지

**요청**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — 일괄 시작/중지

**요청**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**응답**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## 모듈 5: 광고 그룹

### GET /api/ad-groups — 광고 그룹 목록

**파라미터**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — 광고 그룹 생성

**요청**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: 선택 사항, 타겟팅 템플릿에서 targeting JSON을 로드하여 병합

### GET /api/ad-groups/:id — 광고 그룹 상세

### PUT /api/ad-groups/:id — 광고 그룹 업데이트

### POST /api/ad-groups/:id/toggle — 광고 그룹 시작/중지

---

## 모듈 6: 소재

### GET /api/creatives — 소재 목록

**파라미터**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — 소재 상세

---

## 모듈 7: 보고서

### GET /api/reports/summary — 대시보드 집계

5분 캐시.

**파라미터**: `date_start`, `date_end`

**응답**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/reports/custom — 커스텀 보고서

**파라미터**:

| 파라미터 | 설명 |
|------|------|
| `dimensions[]` | 차원: date, platform, campaign |
| `metrics[]` | 지표: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | 시작 날짜 |
| `date_end` | 종료 날짜 |
| `platform` | 플랫폼 필터 |

---

### GET /api/reports/export — 보고서 내보내기

**파라미터**: `format=csv`, `date_start`, `date_end`, `metrics[]`

파일 다운로드 반환（CSV UTF-8 BOM 또는 Excel .xls）.

---

### GET /api/reports/export-dashboard — 대시보드 PDF 내보내기

---

### GET /api/reports/calendar — 집행 캘린더

**파라미터**: `date_start`, `date_end`, `platform`

**응답**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — 예산 경보

**응답**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — 기여도 분석

**파라미터**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**응답**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/reports/attribution/models — 기여도 모델 목록

**응답**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

총 5개 모델.

---

## 모듈 8: 경보

### GET /api/alerts/rules — 경보 규칙 목록

2분 캐시.

**파라미터**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — 경보 규칙 생성

**요청**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/alerts/rules/:id — 경보 규칙 업데이트

### DELETE /api/alerts/rules/:id — 경보 규칙 삭제

### GET /api/alerts/logs — 경보 기록

**파라미터**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — 경보 확인

### GET /api/alerts/unread-count — 미확인 경보 수

30초 캐시. 프론트엔드 30초 폴링.

---

## 모듈 9: 알림

### GET /api/notifications — 알림 목록

**파라미터**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — 미확인 알림 수

### POST /api/notifications/:id/read — 읽음 표시

### POST /api/notifications/read-all — 전체 읽음

---

## 모듈 10: 자동 입찰

### GET /api/bid-rules — 규칙 목록

### POST /api/bid-rules — 규칙 생성

**요청**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**필드 설명**:

| 필드 | 타입 | 설명 |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | 모니터링 지표 |
| condition | gt/gte/lt/lte | 트리거 조건 |
| threshold | decimal | 임계값 |
| action_type | adjust_budget/toggle_pause/toggle_enable | 작업 타입 |
| adjust_step | int (분) | 예산 조정 단위（양수=증가, 음수=감소） |
| budget_min | int | 예산 하한（분） |
| budget_max | int | 예산 상한（분） |
| cooldown_minutes | int | 쿨다운 시간（기본 60） |

### PUT /api/bid-rules/:id — 규칙 업데이트

### DELETE /api/bid-rules/:id — 규칙 삭제

### GET /api/bid-rules/logs — 입찰 이력

**파라미터**: `rule_id`, `campaign_id`

---

## 모듈 11: 타겟팅 템플릿

### GET /api/targeting-templates — 템플릿 목록

**파라미터**: `platform`

### GET /api/targeting-templates/:id — 템플릿 상세

### POST /api/targeting-templates — 템플릿 생성

**요청**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/targeting-templates/:id — 템플릿 업데이트

### DELETE /api/targeting-templates/:id — 템플릿 삭제

---

## 모듈 12: 소재 라이브러리

### GET /api/assets — 소재 목록

**파라미터**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — 소재 업로드

**요청**: `multipart/form-data`, 필드 `file`

- 이미지: 최대 5 MB (jpeg/png/gif/webp)
- 영상: 최대 50 MB (mp4)

**응답**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- CDN 설정 시 `url`은 기본 프로바이더의 `cdn_domain`을 붙여 완전한 HTTPS 주소로 조립됩니다

### POST /api/assets/presign — 프리사인 업로드 URL 획득

**요청**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**응답**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- `key` 형식: `Ymd/32hex.확장자`, 직접 업로드 후 `/api/assets/register`에 회신
- 50 MiB 비디오 등 클라이언트가 객체 스토리지에 직접 업로드; `local` driver에서는 미지원

### POST /api/assets/register — 직접 업로드한 소재 등록

**요청**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**응답**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` 엄격 검증 (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`)으로 경로 탐색 방지

### GET /api/assets/:id — 소재 상세

### DELETE /api/assets/:id — 소재 삭제

---

## Admin 엔드포인트（포트 8789）

### POST /api/admin/login — 관리자 로그인

**요청**: `{ "username": "admin", "password": "..." }`

**응답**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token은 localStorage에 저장
- `csrf_token`은 이후 POST/PUT/DELETE 요청의 `X-CSRF-Token` header에 포함 필요

### GET /api/admin/me — 현재 관리자

### POST /api/admin/logout — 로그아웃

### GET /api/admin/users — 사용자 목록

**파라미터**: `keyword`, `role_id`, `page`, `per_page`

응답의 `id`와 `role_id`는 hashids로 인코딩.

### POST /api/admin/users — 사용자 생성

### PUT /api/admin/users/:id — 사용자 업데이트

### DELETE /api/admin/users/:id — 사용자 비활성화

### GET /api/admin/users/roles — 역할 목록

### GET /api/admin/audit-logs — 감사 로그

**파라미터**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### CDN 프로바이더 관리 (플랫폼 마스터 테넌트 tenant 1만, AdminMiddleware 검증)

### GET /api/admin/cdn/providers — 프로바이더 목록

### POST /api/admin/cdn/providers — 프로바이더 생성

**요청**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss`(알리바바 OSS) / `cos`(텐센트 COS, S3 프로토콜) / `s3`(S3 호환: AWS S3 / Cloudflare R2 / MinIO)
- 자격 증명(access_key/secret_key/cdn_token)은 Encryptable로 필드 단위 암호화, 응답은 마스킹 필드만

### PUT /api/admin/cdn/providers/:id — 프로바이더 수정

### DELETE /api/admin/cdn/providers/:id — 프로바이더 삭제 (기본값은 남은 enabled 프로바이더로 자동 이관)

### PUT /api/admin/cdn/providers/:id/default — 기본값 설정

### PUT /api/admin/cdn/providers/:id/toggle — 활성화/비활성화 (기본값 비활성화 시 자동 이관)

### POST /api/admin/cdn/providers/:id/test — 연결 테스트

**응답**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/admin/cdn/providers/:id/purge — 캐시 퍼지

**요청**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- `cdn_driver`와 `cdn_domain` 필요; 현재 `aliyun` 실제 구현(OpenAPI 서명), cloudflare/cloudfront는 추후 확장

---

## 오류 코드 참조

| code | HTTP | 설명 |
|------|------|------|
| 0 | 200 | 성공 |
| 1 | 200/400 | 일반 비즈니스 오류 |
| 401 | 401 | 미인증 / Token 만료 / IP/UA 불일치 |
| 403 | 403 | 접근 금지（보안 차단） |
| 404 | 404 | 리소스 없음 |
| 422 | 422 | 파라미터 검증 실패 |
| 429 | 429 | 요청 과다 / 로그인 스로틀 / 동시성 제한 |
| 1001 | 200 | 인증 실패（사용자 이름 또는 비밀번호 오류） |

---

## 보안 차단 응답

요청이 보안 미들웨어에 차단되면 403 반환:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## 속도 제한 응답

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

`Retry-After` header에 남은 대기 초 수 포함.
