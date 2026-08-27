# المرحلة 6: إعادة هيكلة بنية Erik Stack

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> إعادة هيكلة شاملة: بادئة قاعدة البيانات، نظام المعرّفات، نظام التشفير، حقوق النشر، معايير الكود

## قائمة التغييرات

| # | التغيير | الحزمة | نطاق التأثير |
|---|------|----|---------|
| 1 | بادئة جداول قاعدة البيانات `ads_` | — | جميع ملفات SQL/الهجرات |
| 2 | المفتاح الأساسي Snowflake ID (بدون تلقائي) | erikwang2013/snowflake-php | جميع النماذج + SQL |
| 3 | تشفير/فك معرّفات API بـ hashids | erikwang2013/hashids | جميع استجابات وحدات التحكم |
| 4 | التحول إلى مصادقة JWT | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | تشفير/فك بيانات API الحساسة | erikwang2013/encryption | طبقة طلبات/استجابات API |
| 6 | تشفير/فك بيانات قاعدة البيانات الحساسة | erikwang2013/encryptable | طبقة نموذج Eloquent |
| 7 | مزامنة/استعلام بيانات ES | erikwang2013/webman-scout | بحث التقارير |
| 8 | أعلام الدول | erikwang2013/season | شارات المنصات في الواجهة الأمامية |
| 9 | بيان حقوق النشر | — | ترويسة جميع الملفات |
| 10 | إزالة بادئة `\` العامة | — | جميع ملفات PHP |
| 11 | إضافة تعليقات لملفات التكوين | — | config/*.php |
| 12 | تخطيط Flutter Web للكمبيوتر | — | مشروع Flutter |
| 13 | تعزيز تصور لوحة الإدارة | — | رسوم لوحة التحكم |
| 14 | تصدير بيانات اللوحة PDF | — | تنسيق تصدير جديد |
| 15 | تصدير Excel (Client+Admin) | — | تعزيز التصدير |
| 16 | تطبيق HarmonyOS | — | مشروع HarmonyOS جديد |

## ترتيب التنفيذ

**المجموعة A: البنية التحتية (التبعيات + المعرّفات + التشفير)**
- تحديث composer.json بإضافة 6 حزم erikwang2013
- إعادة كتابة جميع ملفات هجرة SQL (بادئة ads_ + bigint بدون تلقائي)
- إنشاء خاصية Snowflake ID
- تحديث جميع النماذج (باستخدام SnowflakeTrait)
- تكوين وسيط hashids
- التحول إلى jwt-webman

**المجموعة B: تنظيف الكود**
- إزالة جميع البادئات العامة `\`
- إضافة ترويسة حقوق النشر لجميع الملفات
- إضافة تعليقات لملفات التكوين

**المجموعة C: تعزيز الواجهة الأمامية**
- تعزيز تصور لوحة الإدارة (رسوم أكثر، بيانات فورية)
- تصدير بيانات اللوحة PDF
- تعزيز تصدير Excel

**المجموعة D: Flutter + HarmonyOS**
- مشروع تخطيط Flutter Web للكمبيوتر
- هيكل مشروع HarmonyOS
