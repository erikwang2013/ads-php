# Phase 8: План реализации многоканальных оповещений

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Goal:** закрыть оставшийся разрыв Phase 5 — каналы email/sms в `NotificationService` перевести из echo-заглушек в реальную реализацию (SMTP-почта + универсальный Webhook) с поддержкой конфигурации каналов. Канал web и Redis pub/sub уже реализованы, остаются без изменений.

**Источник:** выводы командного аудита Phase 7 (сверка планов researcher: единственный явно «частично выполненный» пункт = многоканальные оповещения Phase 5, в `ads-alert` нет каталога `channel/`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## Текущее состояние (проверено)

| Компонент | Статус |
|---|---|
| `NotificationService::send()` | `match ($channel)` распределяет web/email/sms; web реально пишет в `erik_notifications`, email/sms — echo-заглушки |
| `AlertRule.channels` | JSON-поле + Eloquent cast array, фронтенд уже отправляет `['web','email','sms']` |
| Admin AlertRuleList.vue | UI выбора каналов уже есть (web заблокирован, email/sms выбираемы) |
| Redis pub/sub | push на канал `alert:new` реализован |
| Конфигурация SMTP/почты | нет (в service/config нет mail-конфигурации) |

## Task 1: Канал email (SMTP)

### Files:
- Create: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, на основе env)
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php` (реализация send(AlertLog, AlertRule))
- Modify: `service/plugin/ads-alert/service/NotificationService.php` (ветка email вызывает EmailChannel, убрать echo-заглушку)
- Modify: `service/composer.json` (если выбран PHPMailer — добавить зависимость; приоритет — реализация на `mail()`/сокетах без зависимостей для лёгкости, оценит исполнитель)

### Ключевые моменты дизайна
- Получатель: из конфигурации AlertRule или арендатора (если нет — поле `email` или значение по умолчанию из конфигурации)
- Тема/тело: переиспользовать шаблон текста sendWeb («сработало оповещение: {rule.name}» + метрика/текущее значение/условие/порог)
- Обработка сбоев: перехват исключений с записью в лог, не влияет на другие каналы и основной поток
- Элегантная деградация при отсутствии конфигурации (лог-уведомление, не прерывать исключением)

## Task 2: Канал Webhook

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON на настроенный URL)
- Modify: в `NotificationService::send()` match добавить ветку `'webhook'`

### Ключевые моменты дизайна
- Источник конфигурации: расширение AlertRule полем `webhook_url` (migration) или конфигурация channels; для минимальных изменений приоритет — добавить в AlertRule колонку `webhook_url` (nullable)
- Нагрузка: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, включая уровень оповещения/метрику/значение/порог/время
- Таймаут и повторы: таймаут соединения 5s, общий таймаут 10s, сбой записывается в лог (без повторов, сохраняем простоту)
- Безопасность: разрешать только http/https, проверку адресов внутренней сети не делать (риск SSRF зафиксировать как известное ограничение, или проверять не-внутренние адреса — оценит и задокументирует исполнитель)

## Task 3: Канал SMS (заглушка шлюза)

### Files:
- Modify: `NotificationService::sendSms` (сохранить заглушку с явным комментарием точки подключения; если исполнитель оценит, что есть лёгкое решение — можно реализовать)

### Ключевые моменты дизайна
- SMS-шлюз (Aliyun/Tencent Cloud) требует AK/SK и платной подписки, на данном этапе сохраняется заглушка, в комментарии указаны шаги подключения
- Опция sms в UI фронтенда остаётся выбираемой, но бэкенд только пишет в лог (явно сообщить пользователю, что шлюз не настроен)

## Task 4: Конфигурация каналов и фронтенд

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue` (если добавляется опция webhook и ввод URL)
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php` (создание/обновление правил принимает webhook_url)
- Modify: `service/plugin/ads-alert/model/AlertRule.php` (в fillable/casts добавить webhook_url)
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER или пояснение к инкрементальному скрипту)

### Приёмка
- [ ] Канал email: после настройки SMTP при срабатывании оповещения приходит письмо; без конфигурации — элегантная деградация
- [ ] Канал webhook: при срабатывании оповещения POST JSON на настроенный URL, поля нагрузки полные
- [ ] Канал sms: остаётся заглушкой, запись в лог
- [ ] Регрессия канала web и Redis pub/sub не затронута
- [ ] Форма правил Admin позволяет настраивать новые поля каналов
- [ ] `php vendor/bin/phpunit --no-coverage` полностью проходит
- [ ] Новые/обновлённые тесты: тесты распределения по каналам AlertEngine/NotificationService
