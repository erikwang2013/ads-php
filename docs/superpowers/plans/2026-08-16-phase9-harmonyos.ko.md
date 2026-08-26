# Phase 9: HarmonyOS 실제 연동 구현 계획

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**목표:** HarmonyOS 단말의 6개 페이지를 시뮬레이션 데이터에서 실제 API 호출(service :8788)로 전환하고, ApiClient의 baseUrl 하드코딩 문제를 수정하며, 로그인을 실제화하여 홍멍(HarmonyOS) 단말을 사용 가능한 세 번째 클라이언트로 만듭니다.

**출처:** Phase 7 팀 감사(mobile-dev 점검: HarmonyOS 6개 페이지 전부 시뮬레이션 데이터, 실제 호출 0곳, ApiClient baseUrl 하드코딩 `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## 현황(확인 완료)

| 컴포넌트 | 상태 |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login 완비; baseUrl 하드코딩 `http://127.0.0.1:8788/api`(Flutter는 동일 출처 상대 경로 `/api` 사용); login() 호출자 없음 |
| `pages/LoginPage.ets` | 시뮬레이션 로그인(setTimeout 1s 후 이동), "replace with actual API call" 주석 |
| `pages/DashboardPage.ets` | `@State` 하드코딩 지표(totalCost=1250000 등) |
| `pages/CampaignListPage.ets` | L187 주석 자리 표시 `/campaigns` |
| `pages/AccountPage.ets` | L138 주석 자리 표시 `/accounts` |
| `pages/AlertPage.ets` | L146 주석 자리 표시 `/alerts` |
| `pages/ReportPage.ets` | L242 주석 자리 표시 `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric 이미 존재 |
| i18n | StringResources.ets(15+ keys) |

## Task 1: ApiClient 강화

### Files:
- 수정: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### 설계 요점
- **baseUrl을 설정 가능하게 변경**: setBaseUrl 유지, 기본값은 여전히 `http://127.0.0.1:8788/api`(실기기/에뮬레이터는 LAN 주소를 가리켜야 하므로 주석 설명); Flutter식 동일 출처 상대 경로 회피(ArkTS는 절대 URL 필수)
- **중복 replayHeaders 버그 수정**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` 중복 전개(get 메서드 내부) → 단일로
- **login() 반환값 맞춤**: service `POST /api/auth/login`이 `{access_token, token_type, expires_in, user}` 반환(`service/plugin/ads-api/controller/v1/AuthController.php` 실제 필드 대조 — token이 아닌 access_token이므로 확인 후 `data.token` 판정 수정 필요)
- **오류 처리**: resp.responseCode가 2xx가 아닐 때 오류 던지기/명확한 오류 정보 반환; JSON.parse 실패 보호
- get/post/put/delete가 `data.data` 반환(ApiResponse 언랩)하는 기존 규약 유지

## Task 2: LoginPage 실제 로그인

### Files:
- 수정: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### 설계 요점
- `handleLogin()`에서 `ApiClient.login(username, password)` 호출; 성공 → setToken + Dashboard 이동; 실패 → toast 오류 정보
- 로딩 상태 isLoading 이미 존재, 재사용
- 오류 메시지는 service가 반환한 message(ApiResponse envelope) 우선, 없으면 일반 문구

## Task 3: 다섯 개 비즈니스 페이지 실제화

### Files:
- 수정: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### 엔드포인트 대조(Phase 7 감사에서 확인, Flutter 수정 후와 일치)
| 페이지 | 호출 | 파싱 |
|---|---|---|
| DashboardPage | `GET /reports/summary`(오늘 구간) | `data.overview` → totalCost/total_impressions/avg_ctr 등(금액은 分, formatFen 이미 있음) |
| CampaignListPage | `GET /campaigns` | `data.list`(페이지네이션) → Campaign model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog 필드(metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom`(date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### 설계 요점
- 페이지 로드(aboutToAppear) 시 요청 트리거; @State 데이터 초기값 빈 값/0으로 설정, 시뮬레이션 값 잔류 방지
- 로드 실패 시 오류 표시 + 재시도(Flutter 페이지의 오류/재시도 패턴 참고)
- 금액 단위: service가 分 단위 숫자 반환, formatFen이 이미 처리
- **파일 신규 추가 없음**, 각 페이지 기존 UI 구조와 i18n 유지

## Task 4: 검증

### 수용
- [ ] ApiClient에 중복 replayHeaders 없음, login 반환 필드가 AuthController와 일치
- [ ] 6개 페이지에 하드코딩 시뮬레이션 비즈니스 데이터 잔류 없음(grep 검증)
- [ ] 5개 비즈니스 페이지 호출 경로가 service 라우트와 일대일 대응(`service/plugin/ads-api/config/route.php` 대조)
- [ ] ArkTS 문법 검사(본 환경에 hvigor/DevEco 툴체인 있으면 실행; 없으면 설명하고 수동 대조)
- [ ] 회귀: service PHPUnit 영향 없음
