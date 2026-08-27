# TDD Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

এই প্রজেক্টের টেস্ট-ড্রিভেন ডেভেলপমেন্ট ভেরিফিকেশন পদ্ধতি।

## ধাপ 1: টেস্ট লিখুন/আপডেট করুন

টেস্টগুলো `service/tests/Unit/`-এ থাকে। `PHPUnit\Framework\TestCase` এক্সটেন্ড করে টেস্ট ক্লাস তৈরি করুন।

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

## ধাপ 2: টেস্ট চালান

```bash
cd service && ./vendor/bin/phpunit
```

প্রত্যাশিত: টেস্ট ফেইল (red)।

## ধাপ 3: ইমপ্লিমেন্ট

টেস্ট পাস করার জন্য ন্যূনতম কোড লিখুন।

## ধাপ 4: আবার টেস্ট চালান

```bash
cd service && ./vendor/bin/phpunit
```

প্রত্যাশিত: টেস্ট পাস (green)।

## ধাপ 5: সম্পূর্ণ ভেরিফিকেশন

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## ধাপ 6: কমিট

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## বিদ্যমান টেস্ট কেস (20 টেস্ট / 41 অ্যাসারশন)

| টেস্ট ক্লাস | টেস্ট | কভার করে |
|-----------|-------|--------|
| FieldMappingTest | 5 | field/status mapping, transformers, empty input, unknown→extra |
| HashidsServiceTest | 5 | encode/decode round trip, uniqueness, invalid hash handling |
| ReportBuilderTest | 3 | metric SQL formulas, dimension filtering, derived metrics |
| CampaignDataTest | 3 | fromArray, defaults, extra fields |
| AdapterRegistryTest | 4 | register/get/has/all, nonexistent returns null |

## প্রি-কমিট চেকলিস্ট

- [ ] `./vendor/bin/phpunit` পাস করে
- [ ] `npx vue-tsc --noEmit` পাস করে
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` পাস করে
- [ ] সব নতুন ফাইলে Copyright header
- [ ] `getenv()` নেই — বদলে `env()` ব্যবহার করুন
- [ ] টেবিল নাম `ads_` প্রিফিক্স ব্যবহার করে
- [ ] গ্লোবাল ক্লাসে লিডিং `\` নেই
- [ ] নেমস্পেসড ফাইলে `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;`
