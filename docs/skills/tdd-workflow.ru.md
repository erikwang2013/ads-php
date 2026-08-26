# TDD Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

Процедура проверки через Test-Driven Development для этого проекта.

## Шаг 1: Написать/обновить тест

Тесты находятся в `service/tests/Unit/`. Создайте класс теста, наследующий `PHPUnit\Framework\TestCase`.

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

## Шаг 2: Запустить тесты

```bash
cd service && ./vendor/bin/phpunit
```

Ожидание: тесты падают (red).

## Шаг 3: Реализовать

Напишите минимальный код, чтобы тесты проходили.

## Шаг 4: Запустить тесты снова

```bash
cd service && ./vendor/bin/phpunit
```

Ожидание: тесты проходят (green).

## Шаг 5: Полная проверка

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Шаг 6: Коммит

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Существующие тестовые случаи (20 тестов / 41 утверждение)

| Класс теста | Тестов | Покрывает |
|-----------|-------|--------|
| FieldMappingTest | 5 | сопоставление полей/статусов, трансформеры, пустой ввод, неизвестное→extra |
| HashidsServiceTest | 5 | encode/decode round trip, уникальность, обработка невалидных хешей |
| ReportBuilderTest | 3 | SQL-формулы метрик, фильтрация измерений, производные метрики |
| CampaignDataTest | 3 | fromArray, значения по умолчанию, дополнительные поля |
| AdapterRegistryTest | 4 | register/get/has/all, несуществующий возвращает null |

## Чек-лист перед коммитом

- [ ] `./vendor/bin/phpunit` проходит
- [ ] `npx vue-tsc --noEmit` проходит
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` проходит
- [ ] Заголовок Copyright на всех новых файлах
- [ ] Никаких `getenv()` — используйте `env()` вместо этого
- [ ] Имена таблиц с префиксом `erik_`
- [ ] Никакого ведущего `\` у глобальных классов
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` в файлах с namespace
