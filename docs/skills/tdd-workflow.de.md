# TDD-Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

Verifizierungsverfahren für testgetriebene Entwicklung (TDD) in diesem Projekt.

## Schritt 1: Test schreiben/aktualisieren

Tests liegen in `service/tests/Unit/`. Eine Testklasse erstellen, die `PHPUnit\Framework\TestCase` erweitert.

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

## Schritt 2: Tests ausführen

```bash
cd service && ./vendor/bin/phpunit
```

Erwartet: Tests schlagen fehl (rot).

## Schritt 3: Implementieren

Minimalen Code schreiben, damit die Tests bestehen.

## Schritt 4: Tests erneut ausführen

```bash
cd service && ./vendor/bin/phpunit
```

Erwartet: Tests bestehen (grün).

## Schritt 5: Vollständige Verifizierung

```bash
# PHP-Syntaxprüfung (alle Dateien)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript-Typprüfung
cd admin/public/web && npx vue-tsc --noEmit

# Alle Tests
cd service && ./vendor/bin/phpunit --testdox
```

## Schritt 6: Committen

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Vorhandene Testfälle (20 Tests / 41 Assertions)

| Testklasse | Tests | Abdeckung |
|-----------|-------|--------|
| FieldMappingTest | 5 | Feld-/Statuszuordnung, Transformer, leere Eingabe, unbekannt→extra |
| HashidsServiceTest | 5 | encode/decode Roundtrip, Eindeutigkeit, Behandlung ungültiger Hashes |
| ReportBuilderTest | 3 | Metrik-SQL-Formeln, Dimensionsfilterung, abgeleitete Metriken |
| CampaignDataTest | 3 | fromArray, Standardwerte, extra Felder |
| AdapterRegistryTest | 4 | register/get/has/all, nicht vorhanden liefert null |

## Pre-Commit-Checkliste

- [ ] `./vendor/bin/phpunit` besteht
- [ ] `npx vue-tsc --noEmit` besteht
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` besteht
- [ ] Copyright-Header auf allen neuen Dateien
- [ ] Kein `getenv()` — stattdessen `env()` verwenden
- [ ] Tabellennamen mit `ads_`-Präfix
- [ ] Kein führendes `\` vor globalen Klassen
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` in namespaced-Dateien
