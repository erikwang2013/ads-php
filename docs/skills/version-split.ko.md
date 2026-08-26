# 버전 분리

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Lite/Standard/Full 3개 버전의 기능 차이를 관리합니다.

## 3계층 구조

```
Lite (오픈소스 MIT)       Standard (상업)            Full (상업)
├── 7 컨트롤러            ├── 11 컨트롤러            ├── 17 컨트롤러
├── 7 미들웨어            ├── 11 미들웨어            ├── 15 미들웨어
├── 3 cron                ├── 4 cron                ├── 6 cron
├── 8 테이블              ├── 13 테이블             ├── 18 테이블
├── 7 Vue 페이지          ├── 13 Vue 페이지         ├── 17 Vue 페이지
└── 26 API 엔드포인트     └── 44 API 엔드포인트     └── 62 API 엔드포인트
```

## 분리 절차

### 1. 기능 브랜치 생성

```bash
git checkout -b feature/lite   # 간소화 버전
git checkout -b feature/standard  # 표준 버전
```

### 2. 대상 버전에 없는 기능 삭제

**Full에서 Standard로 분리 (삭제 대상)**:

- 컨트롤러: BidRuleController, TargetingTemplateController, AssetController
- 서비스: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- 모델: BidRule, BidLog, TargetingTemplate
- 미들웨어: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- 작업: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- 라우트: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Standard에서 Lite로 분리 (삭제 대상)**:

- 컨트롤러: AdGroupController, CreativeController, AlertController, NotificationController
- 서비스: AlertEngine, NotificationService
- 모델: AlertRule, AlertLog
- 미들웨어: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- 작업: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- DataSyncTask에서 adgroup/creative 동기화 제거

### 3. 설정 파일 업데이트

분리할 때마다 업데이트:
- `route.php`: 해당 라우트와 import 삭제
- `middleware.php` (service+admin): 미들웨어 체인 단순화
- `cron.php`: 예약 작업 정리
- `router/index.ts` + `SideNav.vue` (Vue): 페이지 라우트와 메뉴 제거
- `router.dart` + `menu_config.dart` (Flutter): 동기 업데이트

### 4. 검증

```bash
php -l 문법 검사
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 오류 0개
dart analyze           # 오류 0개
grep -rn 삭제한 클래스 이름    # 잔여 참조 0개
```
