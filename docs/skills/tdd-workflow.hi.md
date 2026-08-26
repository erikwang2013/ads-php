# TDD Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

इस प्रोजेक्ट के लिए टेस्ट-ड्रिवेन डेवलपमेंट सत्यापन प्रक्रिया।

## चरण 1: टेस्ट लिखें/अपडेट करें

टेस्ट `service/tests/Unit/` में रहते हैं। `PHPUnit\Framework\TestCase` विस्तारित करने वाली टेस्ट क्लास बनाएँ।

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

## चरण 2: टेस्ट चलाएँ

```bash
cd service && ./vendor/bin/phpunit
```

अपेक्षा: टेस्ट असफल (लाल)।

## चरण 3: इम्प्लीमेंट करें

टेस्ट पास कराने के लिए न्यूनतम कोड लिखें।

## चरण 4: फिर से टेस्ट चलाएँ

```bash
cd service && ./vendor/bin/phpunit
```

अपेक्षा: टेस्ट पास (हरा)।

## चरण 5: पूर्ण सत्यापन

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## चरण 6: कमिट

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## मौजूदा टेस्ट केस (20 टेस्ट / 41 एसर्शन)

| टेस्ट क्लास | टेस्ट | कवर करता है |
|-----------|-------|--------|
| FieldMappingTest | 5 | फ़ील्ड/स्थिति मैपिंग, ट्रांसफॉर्मर, खाली इनपुट, unknown→extra |
| HashidsServiceTest | 5 | encode/decode राउंड ट्रिप, विशिष्टता, अमान्य hash हैंडलिंग |
| ReportBuilderTest | 3 | मेट्रिक SQL फ़ॉर्मूले, आयाम फ़िल्टरिंग, व्युत्पन्न मेट्रिक्स |
| CampaignDataTest | 3 | fromArray, डिफ़ॉल्ट, अतिरिक्त फ़ील्ड |
| AdapterRegistryTest | 4 | register/get/has/all, अस्तित्वहीन पर null लौटता है |

## प्री-कमिट चेकलिस्ट

- [ ] `./vendor/bin/phpunit` पास
- [ ] `npx vue-tsc --noEmit` पास
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` पास
- [ ] सभी नई फ़ाइलों पर Copyright header
- [ ] कोई `getenv()` नहीं — इसके बजाय `env()` का उपयोग करें
- [ ] टेबल नामों में `erik_` प्रीफ़िक्स
- [ ] ग्लोबल क्लास पर अग्रणी `\` नहीं
- [ ] नेमस्पेस वाली फ़ाइलों में `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;`
