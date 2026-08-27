# TDD Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

このプロジェクトのテスト駆動開発（TDD）検証手順。

## Step 1: テストの作成/更新

テストは `service/tests/Unit/` にあります。`PHPUnit\Framework\TestCase` を継承するテストクラスを作成します。

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

## Step 2: テストの実行

```bash
cd service && ./vendor/bin/phpunit
```

期待値: テストが失敗する（レッド）。

## Step 3: 実装

テストが通る最小限のコードを書きます。

## Step 4: テストを再実行

```bash
cd service && ./vendor/bin/phpunit
```

期待値: テストが通る（グリーン）。

## Step 5: 完全な検証

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Step 6: コミット

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## 既存のテストケース (20 tests / 41 assertions)

| Test Class | Tests | Covers |
|-----------|-------|--------|
| FieldMappingTest | 5 | field/status mapping, transformers, empty input, unknown→extra |
| HashidsServiceTest | 5 | encode/decode round trip, uniqueness, invalid hash handling |
| ReportBuilderTest | 3 | metric SQL formulas, dimension filtering, derived metrics |
| CampaignDataTest | 3 | fromArray, defaults, extra fields |
| AdapterRegistryTest | 4 | register/get/has/all, nonexistent returns null |

## コミット前チェックリスト

- [ ] `./vendor/bin/phpunit` passes
- [ ] `npx vue-tsc --noEmit` passes
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` passes
- [ ] Copyright header on all new files
- [ ] No `getenv()` — use `env()` instead
- [ ] Table names use `ads_` prefix
- [ ] No leading `\` on global classes
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` in namespaced files
