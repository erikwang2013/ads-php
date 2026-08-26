# Phase 8: 경보 다중 채널 구축 구현 계획

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**목표:** Phase 5의 잔여 공백을 보완합니다 — `NotificationService`의 email/sms 채널을 echo 스텁에서 실제 구현으로 업그레이드(SMTP 메일 + 범용 Webhook)하고 채널 구성을 지원합니다. web 채널과 Redis pub/sub은 이미 구현되어 그대로 유지합니다.

**출처:** Phase 7 팀 감사 결론(researcher 계획 대조: 유일하게 명확한 "부분 완료" 항목 = Phase 5 경보 다중 채널, `ads-alert`에 `channel/` 디렉터리 부재)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## 현황(확인 완료)

| 컴포넌트 | 상태 |
|---|---|
| `NotificationService::send()` | `match ($channel)`로 web/email/sms 분기; web은 실제로 `erik_notifications`에 기록, email/sms는 echo 스텁 |
| `AlertRule.channels` | JSON 필드 + Eloquent cast array, 프론트엔드가 이미 `['web','email','sms']` 제출 |
| Admin AlertRuleList.vue | 채널 선택 UI 이미 존재(web 잠금, email/sms 선택 가능) |
| Redis pub/sub | `alert:new` 채널 푸시 구현 완료 |
| SMTP/메일 설정 | 없음(service/config에 mail 설정 없음) |

## Task 1: 메일 채널(SMTP)

### Files:
- 생성: `service/config/mail.php`(smtp host/port/user/pass/from/from_name/encryption, env 기반)
- 생성: `service/plugin/ads-alert/service/channel/EmailChannel.php`(send(AlertLog, AlertRule) 구현)
- 수정: `service/plugin/ads-alert/service/NotificationService.php`(email 분기가 EmailChannel 호출, echo 스텁 제거)
- 수정: `service/composer.json`(PHPMailer 선택 시 의존성 추가; 가벼움을 유지하기 위해 의존성 없는 `mail()`/socket 구현을 우선 고려, 구현자가 평가)

### 설계 요점
- 수신자: AlertRule 구성 또는 테넌트 구성에서 읽음(없으면 `email` 필드 또는 구성 기본값 사용)
- 제목/본문: sendWeb의 문구 템플릿 재사용("경보 트리거: {rule.name}" + 지표/현재 값/조건/임계값)
- 실패 처리: 예외를 잡아 로그 기록, 다른 채널과 메인 흐름에 영향 없음
- 구성 누락 시 우아한 디그레이드(log로 안내, 예외 던져 중단하지 않음)

## Task 2: Webhook 채널

### Files:
- 생성: `service/plugin/ads-alert/service/channel/WebhookChannel.php`(구성된 URL에 POST JSON)
- 수정: `NotificationService::send()` match에 `'webhook'` 분기 추가

### 설계 요점
- 구성 출처: AlertRule에 `webhook_url` 필드 확장(migration) 또는 channels 구성; 최소 변경을 위해 AlertRule에 `webhook_url` 컬럼 추가 우선(널 허용)
- 페이로드: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, 경보 레벨/지표/값/임계값/시간 포함
- 타임아웃과 재시도: 연결 타임아웃 5s, 총 타임아웃 10s, 실패 시 로그 기록(재시도 없음, 단순 유지)
- 보안: http/https만 허용, 내부 네트워크 주소 검증 없음(SSRF 위험을 알려진 제한으로 기록, 또는 비내부망 검증 — 구현자가 평가하고 기록)

## Task 3: SMS 채널(게이트웨이 자리 표시)

### Files:
- 수정: `NotificationService::sendSms`(자리 표시 유지, 접속 지점 명확히 주석; 구현자가 경량 방안 평가 시 구축 가능)

### 설계 요점
- SMS 게이트웨이(알리 클라우드/텐센트 클라우드)는 AK/SK와 유료 필요, 본 단계는 자리 표시 구현 유지, 접속 단계를 주석으로 명시
- 프론트엔드 UI의 sms 옵션은 선택 가능하게 유지하되 백엔드는 로그만 기록(사용자에게 게이트웨이 미구성임을 명확히 안내)

## Task 4: 채널 구성과 프론트엔드

### Files:
- 수정: `admin/public/web/src/views/alert/AlertRuleList.vue`(webhook 옵션과 URL 입력 추가 시)
- 수정: `service/plugin/ads-api/controller/v1/AlertController.php`(규칙 생성/업데이트가 webhook_url 수용)
- 수정: `service/plugin/ads-alert/model/AlertRule.php`(fillable/casts에 webhook_url 추가)
- 수정: `service/plugin/ads-alert/migration/create_alerts.sql`(ALTER 또는 증분 스크립트 설명)

### 수용
- [ ] email 채널: SMTP 구성 후 경보 트리거 시 메일 수신 가능; 미구성 시 우아한 디그레이드
- [ ] webhook 채널: 경보 트리거 시 구성된 URL에 POST JSON, 페이로드 필드 완비
- [ ] sms 채널: 자리 표시 유지, 로그 기록
- [ ] web 채널과 Redis pub/sub 회귀 영향 없음
- [ ] Admin 규칙 폼에서 새 채널 필드 구성 가능
- [ ] `php vendor/bin/phpunit --no-coverage` 전부 통과
- [ ] 신규/업데이트 테스트: AlertEngine/NotificationService 채널 분기 테스트
