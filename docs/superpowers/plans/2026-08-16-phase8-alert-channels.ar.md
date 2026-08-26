# المرحلة 8: خطة تنفيذ قنوات التنبيهات المتعددة

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**الهدف:** سدّ الفجوة المتبقية من المرحلة 5 — ترقية قناتي email/sms في `NotificationService` من stubs echo إلى تنفيذات حقيقية (بريد SMTP + Webhook عام)، ودعم تكوين القنوات. قناتا web وRedis pub/sub منفذتان بالفعل، وتبقيان دون تغيير.

**المصدر:** استنتاج تدقيق فريق المرحلة 7 (مقارنة تخطيط researcher: البند الوحيد المحدد "مكتمل جزئيًا" = قنوات تنبيهات المرحلة 5 المتعددة، `ads-alert` تفتقر إلى دليل `channel/`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## الوضع الحالي (تم التحقق منه)

| المكوّن | الحالة |
|---|---|
| `NotificationService::send()` | توزيع `match ($channel)` على web/email/sms؛ web يكتب فعليًا في `erik_notifications`، email/sms كـ echo stubs |
| `AlertRule.channels` | حقل JSON + تحويل Eloquent cast array، الواجهة الأمامية ترسل بالفعل `['web','email','sms']` |
| Admin AlertRuleList.vue | توجد واجهة اختيار القنوات (web مثبتة، email/sms اختيارية) |
| Redis pub/sub | دفع قناة `alert:new` منفذ |
| تكوين SMTP/البريد | غير موجود (لا يوجد إعداد mail في service/config) |

## المهمة 1: قناة البريد (SMTP)

### الملفات:
- إنشاء: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption، بمحرك env)
- إنشاء: `service/plugin/ads-alert/service/channel/EmailChannel.php` (تنفيذ send(AlertLog, AlertRule))
- تعديل: `service/plugin/ads-alert/service/NotificationService.php` (فرع email يستدعي EmailChannel، إزالة echo stub)
- تعديل: `service/composer.json` (إذا اختير PHPMailer تُضاف تبعية؛ يُفضل تنفيذ `mail()`/socket بدون تبعيات للحفاظ على الخفة، حسب تقييم المنفذ)

### نقاط التصميم
- المستلم: يُقرأ من تكوين AlertRule أو تكوين المستأجر (إن لم يوجد، استخدم حقل `email` أو الافتراضي في التكوين)
- الموضوع/النص: إعادة استخدام قالب نص sendWeb ("تم إطلاق التنبيه: {rule.name}" + المقياس/القيمة الحالية/الشرط/العتبة)
- معالجة الفشل: التقاط الاستثناءات وتسجيلها، دون التأثير على القنوات الأخرى أو التدفق الرئيسي
- انحطاط أنيق عند غياب التكوين (إشعار log، دون إلقاء استثناء يقاطع)

## المهمة 2: قناة Webhook

### الملفات:
- إنشاء: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON إلى URL المكوّن)
- تعديل: إضافة فرع `'webhook'` إلى `NotificationService::send()` match

### نقاط التصميم
- مصدر التكوين: توسيع AlertRule بحقل `webhook_url` (migration) أو تكوين channels؛ لأقل تغيير، يُفضل إضافة عمود `webhook_url` إلى AlertRule (قابل للفراغ)
- الحمولة: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`، تتضمن مستوى التنبيه/المقياس/القيمة/العتبة/الوقت
- المهلة وإعادة المحاولة: مهلة الاتصال 5 ثوانٍ، المهلة الكلية 10 ثوانٍ، تسجيل الفشل (بدون إعادة محاولة، لإبقائه بسيطًا)
- الأمان: السماح بـ http/https فقط، بدون التحقق من عنوان الشبكة الداخلية (مخاطرة SSRF مسجلة كقيود معروفة، أو التحقق من عدم كونها داخلية — حسب تقييم المنفذ وتسجيله)

## المهمة 3: قناة SMS (بوابة كعنصر نائب)

### الملفات:
- تعديل: `NotificationService::sendSms` (الإبقاء على العنصر النائب، مع تعليق واضح لنقطة الدمج؛ إذا كان لدى المنفذ حل خفيف يمكن تنفيذه)

### نقاط التصميم
- بوابة SMS (Alibaba Cloud / Tencent Cloud) تتطلب AK/SK ودفعًا، تُبقي هذه المرحلة التنفيذ كعنصر نائب، مع تعليق يوضح خطوات الدمج
- خيار sms في واجهة المستخدم الأمامية يبقى اختياريًا لكن الواجهة الخلفية تسجل log فقط (إبلاغ المستخدم بوضوح أن البوابة غير مكوّنة)

## المهمة 4: تكوين القنوات والواجهة الأمامية

### الملفات:
- تعديل: `admin/public/web/src/views/alert/AlertRuleList.vue` (إضافة خيار webhook ومدخل URL إن لزم)
- تعديل: `service/plugin/ads-api/controller/v1/AlertController.php` (إنشاء/تحديث القواعد يقبل webhook_url)
- تعديل: `service/plugin/ads-alert/model/AlertRule.php` (إضافة webhook_url إلى fillable/casts)
- تعديل: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER أو توضيح سكربت تزايدي)

### القبول
- [ ] قناة email: بعد تكوين SMTP يصل البريد عند إطلاق التنبيه؛ انحطاط أنيق عند عدم التكوين
- [ ] قناة webhook: عند إطلاق التنبيه POST JSON إلى URL المكوّن، وحقول الحمولة كاملة
- [ ] قناة sms: تبقى عنصرًا نائبًا، تسجيل log
- [ ] قناة web وRedis pub/sub انحدار غير متأثر
- [ ] نموذج قواعد Admin يمكنه تكوين حقول القنوات الجديدة
- [ ] `php vendor/bin/phpunit --no-coverage` ناجح بالكامل
- [ ] اختبارات جديدة/محدّثة: اختبارات توزيع القنوات في AlertEngine/NotificationService
