# 사용 안내

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> 설치 및 배포는 README의 « 빠른 시작 » 섹션을 참조하세요. 이 문서는 설치 완료 후의 전체 사용 흐름을 다룹니다.

---

## 1. 첫 로그인

설치 완료 후 관리 콘솔을 엽니다:

- 원클릭 설치 / Docker: `http://localhost`
- 로컬 개발: `http://localhost:8789`

설치 마법사에서 설정한 관리자 사용자 이름과 비밀번호로 로그인합니다. 로그인하면 대시보드에 8개의 KPI 지표 카드(총 비용, 노출, 클릭, 전환, CTR, CVR, 평균 CPC, 평균 CPA), 일별 비용 추세 꺾은선 그래프, 플랫폼 비교 막대 그래프, TOP 10 캠페인이 표시됩니다.

비밀번호 또는 계정 정보 변경: 시스템 관리 → 사용자 관리.

---

## 2. 플랫폼 권한 부여

시스템은 **국내 16개 + 국제 13개 플랫폼**을 지원하며, 모두 « 계정 관리 → 계정 연동 » 을 통해 권한을 부여합니다.

### OAuth2 플랫폼(대부분)

1. « 계정 연동 » 페이지에서 대상 플랫폼을 선택하고 « 권한 부여 » 클릭
2. 브라우저가 플랫폼 로그인 페이지로 리디렉션되고, 로그인 후 액세스를 승인
3. 콜백 후 시스템이 액세스 토큰을 자동 저장

권한이 부여된 플랫폼은 계정 목록에 표시됩니다. 만료된 토큰은 `TokenRefreshTask`가 자동 갱신합니다(매시간 55분) — 수동 개입 불필요.

### API Key 플랫폼

360, Sogou, Umeng 같은 플랫폼은 API Key 인증을 사용합니다: « 계정 연동 » 페이지에서 API Key(및 서명 파라미터)를 수동으로 입력하고 저장하면 동기화가 시작됩니다.

> 국내 16개 플랫폼: Juliang(오션엔진), Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou, Xiaohongshu, Weibo, Bilibili, Youku Ads, Meituan Ads, Zhihu Ads, Qihoo360, Sogou, Umeng, JD, Pinduoduo Ads
>
> 국제 13개 플랫폼: Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. 계정 연동 및 크리에이티브 라이브러리 업로드

### 계정 관리

플랫폼 권한 부여 후 계정은 « 계정 관리 » 목록에 표시됩니다. 각 계정은 동기화 참여 여부를 개별적으로 제어할 수 있습니다(`sync_enabled`). 광고 계층은 캠페인 → 광고 그룹 → 크리에이티브의 3단계 구조입니다.

### 크리에이티브 라이브러리

« 크리에이티브 라이브러리 »는 갤러리 스타일 브라우징과 함께 이미지/동영상 업로드를 지원하며, 광고 크리에이티브에서 사용됩니다. 업로드된 에셋은 선택적으로 CDN 스토리지를 사용할 수 있습니다(아래 참조).

### CDN 스토리지 제공자 구성

시스템에는 여러 드라이버를 지원하는 내장 스토리지 추상화가 있으며, 여러 제공자를 동시에 구성할 수 있습니다:

| 드라이버 | 설명 |
|---------|------|
| 로컬 스토리지 | 기본 드라이버, 서버 디스크에 저장 |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| S3 호환 | S3CompatibleStorage(AWS S3, Qiniu Cloud, MinIO 등 호환) |

« CDN 제공자 » 페이지에서 제공자를 추가하고 해당 키/리전 파라미터를 입력하면 활성화됩니다.

### 사전 서명 업로드 및 캐시 퍼지

- **사전 서명 업로드**: 서버가 업로드마다 기한이 있는 사전 서명 URL(OSS/S3 PUT)을 발급하고, 브라우저나 모바일 클라이언트가 애플리케이션 서버를 거치지 않고 객체 스토리지에 직접 업로드 — 대역폭과 부하 절감
- **캐시 퍼지**: 에셋 업데이트 또는 삭제 후 CDN 캐시 퍼지를 트리거하여 클라이언트가 항상 최신 콘텐츠를 받도록 합니다

---

## 4. 데이터 동기화

동기화는 6개의 예약 작업으로 구동됩니다(webman crontab 플러그인이 프로세스 내에서 스케줄 — 외부 crontab 불필요):

| 작업 | 빈도 | 역할 |
|------|------|------|
| RetrySyncTask | 3분마다 | 마지막 실패한 동기화 재시도 |
| AlertCheckTask | 5분마다 | 알림 규칙 평가 |
| DataSyncTask | 10분마다 | 캠페인/광고 그룹/크리에이티브 및 보고서 동기화(지난 2일, 9개 지표) |
| BidCheckTask | 10분마다 | 자동 입찰 규칙 확인 |
| BudgetCheckTask | 15분마다 | 예산 알림 확인 |
| TokenRefreshTask | 매시간 55분 | 만료된 플랫폼 토큰 갱신 |

작업 구성은 `service/plugin/ads-task/config/cron.php`에 있으며 빈도를 수정할 수 있습니다. 동기화 상태는 « 데이터 동기화 » 페이지에서 확인할 수 있고, 계정별 ON/OFF 스위치는 « 계정 관리 »에 있습니다.

---

## 5. 보고서 분석

### 대시보드

8개의 KPI 지표 카드 + 일별 추세 꺾은선 그래프 + 플랫폼 비교 막대 그래프 + TOP 10 캠페인, 날짜 범위 필터와 원클릭 PDF/Excel 내보내기 지원.

### 커스텀 보고서

- **차원**: date, platform, campaign
- **지표**: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- 차원 조합 쿼리 및 정렬 지원

### 어트리뷰션 분석

내장 크로스 플랫폼 어트리뷰션 엔진은 **5가지 어트리뷰션 모델**(first_touch, last_touch, linear, time_decay, position_based)을 지원하며 30일 룩백 윈도우를 가집니다. « 어트리뷰션 분석 » 페이지에서 모델과 날짜 범위를 선택하면 각 채널의 기여도를 확인할 수 있습니다.

### 캠페인 캘린더

« 캠페인 캘린더 »는 각 캠페인의 게재 일정을 캘린더 보기로 표시하여 일일 게재 리듬을 한눈에 확인할 수 있습니다.

### 내보내기

보고서는 3가지 내보내기 형식을 지원합니다:

- **CSV**(UTF-8 BOM, Excel에서 깨짐 없이 바로 열림)
- **Excel**(HTML .xls)
- **PDF**(HTML 인쇄 레이아웃)

---

## 6. 알림 및 통지

### 알림 규칙

« 알림 규칙 » 페이지에서 규칙을 생성합니다: 모니터링 대상(예산/비용/노출/클릭 등), 임계값과 비교 방식, 적용 범위, 통지 채널을 선택합니다. 활성화된 규칙은 `AlertCheckTask`가 5분마다 평가하며 조건이 일치하면 트리거됩니다.

### 통지 채널

| 채널 | 설명 |
|------|------|
| Web | 앱 내 통지, « 통지 센터 »에서 확인 |
| Email | 이메일 전송(SMTP, `mail()` 폴백 포함); 알림 규칙에서 수신자 주소 구성 |
| SMS | 문자 메시지 전송 |
| Webhook | 구성된 콜백 URL로 JSON POST; WeCom/DingTalk/Feishu 등과 연동 가능 |

알림 기록은 « 알림 로그 » 페이지에서 확인할 수 있습니다.

---

## 7. 모바일 앱

### Flutter 앱(12페이지: 로그인/대시보드/계정/캠페인/광고 그룹/크리에이티브/보고서/입찰/알림/통지 등)

```bash
cd apps/flutter
flutter run -d chrome     # Web PC
flutter run -d android    # Android 휴대폰
```

### HarmonyOS 앱

DevEco Studio로 `apps/harmonyos` 디렉터리를 열어 실행합니다.

---

## 8. 멀티테넌시

시스템에는 내장 멀티테넌트 플러그인(ads-tenant)이 있습니다:

- **테넌트 식별**: `TenantIdentify` 미들웨어가 요청별로 현재 테넌트를 식별
- **데이터 격리**: 두 가지 모드 — 공유 데이터베이스를 `tenant_id`로 격리, 또는 테넌트별 독립 데이터베이스(`db_type`)
- **쿼터 관리**: `QuotaService`가 테넌트 쿼터(계정 수, 에셋 수 등)를 검증하고, 쿼터를 초과하는 요청은 거부

---

## 관련 문서

- [기능 설계 문서](features.ko.md) — 21개 모듈/업무 흐름
- [API 인터페이스 문서](api.ko.md) — 모든 인터페이스 정의
- [아키텍처 설계 문서](architecture.ko.md) — 배포/보안/데이터 모델
