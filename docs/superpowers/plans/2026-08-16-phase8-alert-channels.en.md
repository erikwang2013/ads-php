# Phase 8: Alert Multi-Channel Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Goal:** Fill the Phase 5 leftover gap — upgrade the `NotificationService` email/sms channels from echo stubs to real implementations (SMTP email + generic Webhook), and support channel configuration. The web channel and Redis pub/sub are already implemented and stay unchanged.

**Source:** Phase 7 team audit conclusion (researcher planning comparison: the only explicit "partially complete" item = Phase 5 alert multi-channel, `ads-alert` lacks the `channel/` directory)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## Current State (verified)

| Component | Status |
|---|---|
| `NotificationService::send()` | `match ($channel)` dispatches web/email/sms; web really writes to `ads_notifications`, email/sms are echo stubs |
| `AlertRule.channels` | JSON field + Eloquent cast array, frontend already submits `['web','email','sms']` |
| Admin AlertRuleList.vue | Already has channel checkbox UI (web locked, email/sms optional) |
| Redis pub/sub | `alert:new` channel push implemented |
| SMTP/email config | None (service/config has no mail config) |

## Task 1: Email channel (SMTP)

### Files:
- Create: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, env-driven)
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php` (implements send(AlertLog, AlertRule))
- Modify: `service/plugin/ads-alert/service/NotificationService.php` (email branch calls EmailChannel, remove echo stub)
- Modify: `service/composer.json` (add dependency if PHPMailer is chosen; prefer a dependency-free `mail()`/socket implementation to stay lightweight, implementation to be evaluated by the implementer)

### Design points
- Recipient: read from AlertRule config or tenant config (fallback to `email` field or config default)
- Subject/body: reuse the sendWeb copy template ("告警触发: {rule.name}" + metric/current value/condition/threshold)
- Failure handling: catch exceptions and log, does not affect other channels or the main flow
- Graceful degradation when config is missing (log a notice, do not throw and interrupt)

## Task 2: Webhook channel

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON to the configured URL)
- Modify: `NotificationService::send()` match adds a `'webhook'` branch

### Design points
- Config source: extend AlertRule with a `webhook_url` field (migration) or channels config; for minimal change, prefer adding a `webhook_url` column (nullable) to AlertRule
- Payload: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, including alert level/metric/value/threshold/time
- Timeout and retry: connect timeout 5s, total timeout 10s, log on failure (no retry, keep it simple)
- Security: only allow http/https, no intranet address validation (SSRF risk noted as a known limitation, or validate non-intranet — to be evaluated and documented by the implementer)

## Task 3: SMS channel (gateway placeholder)

### Files:
- Modify: `NotificationService::sendSms` (keep placeholder, clearly comment the integration point; a lightweight approach may be implemented if evaluated feasible)

### Design points
- SMS gateways (Aliyun/Tencent Cloud) require AK/SK and payment; this phase keeps a placeholder implementation with commented integration steps
- The sms option in the frontend UI stays selectable, but the backend only logs (clearly telling the user no gateway is configured)

## Task 4: Channel config and frontend

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue` (add webhook option and URL input if applicable)
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php` (rule create/update accepts webhook_url)
- Modify: `service/plugin/ads-alert/model/AlertRule.php` (add webhook_url to fillable/casts)
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER or document incremental script)

### Acceptance
- [ ] email channel: after configuring SMTP, triggered alerts arrive by email; graceful degradation when not configured
- [ ] webhook channel: POST JSON to the configured URL on alert trigger, payload fields complete
- [ ] sms channel: stays a placeholder, logs
- [ ] web channel and Redis pub/sub regression unaffected
- [ ] Admin rule form can configure the new channel fields
- [ ] `php vendor/bin/phpunit --no-coverage` all pass
- [ ] New/updated tests: AlertEngine/NotificationService channel dispatch tests
