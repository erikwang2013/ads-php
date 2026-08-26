# سير عمل TDD

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

إجراء التحقق من التطوير الموجه بالاختبارات (TDD) لهذا المشروع.

## الخطوة 1: كتابة/تحديث الاختبار

الاختبارات موجودة في `service/tests/Unit/`. أنشئ فئة اختبار تمتد `PHPUnit\Framework\TestCase`.

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class YourServiceTest extends TestCase
{
    public function testExpectedBehavior(): void
    {
        $this->assertEquals(expected, actual);
    }
}
```

## الخطوة 2: تشغيل الاختبارات

```bash
cd service && ./vendor/bin/phpunit
```

المتوقع: تفشل الاختبارات (حمراء).

## الخطوة 3: التنفيذ

اكتب الحد الأدنى من الكود لاجتياز الاختبارات.

## الخطوة 4: تشغيل الاختبارات مجددًا

```bash
cd service && ./vendor/bin/phpunit
```

المتوقع: تنجح الاختبارات (خضراء).

## الخطوة 5: التحقق الكامل

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## الخطوة 6: الالتزام

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## حالات الاختبار الحالية (20 اختبارًا / 41 تأكيدًا)

| فئة الاختبار | الاختبارات | يغطي |
|-----------|-------|--------|
| FieldMappingTest | 5 | تعيين الحقول/الحالات، المحولات، الإدخال الفارغ، unknown→extra |
| HashidsServiceTest | 5 | encode/decode round trip، التفرد، معالجة التجزئة غير الصالحة |
| ReportBuilderTest | 3 | صيغ مقاييس SQL، تصفية الأبعاد، المقاييس المشتقة |
| CampaignDataTest | 3 | fromArray، القيم الافتراضية، الحقول الإضافية |
| AdapterRegistryTest | 4 | register/get/has/all، غير الموجود يُرجع null |

## قائمة التحقق قبل الالتزام

- [ ] اجتياز `./vendor/bin/phpunit`
- [ ] اجتياز `npx vue-tsc --noEmit`
- [ ] اجتياز `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`
- [ ] ترويسة حقوق النشر على جميع الملفات الجديدة
- [ ] عدم استخدام `getenv()` — استخدم `env()` بدلاً منه
- [ ] أسماء الجداول تستخدم بادئة `erik_`
- [ ] لا توجد `\` بادئة على الفئات العامة
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` في الملفات ذات المساحات الاسمية
