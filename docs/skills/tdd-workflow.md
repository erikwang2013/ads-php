# TDD Workflow

Test-Driven Development verification procedure for this project.

## Step 1: Write/Update Test

Tests live in `service/tests/Unit/`. Create a test class extending `PHPUnit\Framework\TestCase`.

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

## Step 2: Run Tests

```bash
cd service && ./vendor/bin/phpunit
```

Expected: Tests fail (red).

## Step 3: Implement

Write minimal code to make tests pass.

## Step 4: Run Tests Again

```bash
cd service && ./vendor/bin/phpunit
```

Expected: Tests pass (green).

## Step 5: Full Verification

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Step 6: Commit

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Existing Test Cases (20 tests / 41 assertions)

| Test Class | Tests | Covers |
|-----------|-------|--------|
| FieldMappingTest | 5 | field/status mapping, transformers, empty input, unknown→extra |
| HashidsServiceTest | 5 | encode/decode round trip, uniqueness, invalid hash handling |
| ReportBuilderTest | 3 | metric SQL formulas, dimension filtering, derived metrics |
| CampaignDataTest | 3 | fromArray, defaults, extra fields |
| AdapterRegistryTest | 4 | register/get/has/all, nonexistent returns null |

## Pre-commit Checklist

- [ ] `./vendor/bin/phpunit` passes
- [ ] `npx vue-tsc --noEmit` passes
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` passes
- [ ] Copyright header on all new files
- [ ] No `getenv()` — use `env()` instead
- [ ] Table names use `erik_` prefix
- [ ] No leading `\` on global classes
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` in namespaced files
