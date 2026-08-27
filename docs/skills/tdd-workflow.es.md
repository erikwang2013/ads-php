# TDD Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

Procedimiento de verificación de desarrollo dirigido por pruebas (TDD) para este proyecto.

## Paso 1: Escribir/Actualizar la prueba

Las pruebas viven en `service/tests/Unit/`. Crea una clase de prueba que extienda `PHPUnit\Framework\TestCase`.

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

## Paso 2: Ejecutar las pruebas

```bash
cd service && ./vendor/bin/phpunit
```

Esperado: Las pruebas fallan (rojo).

## Paso 3: Implementar

Escribe el código mínimo para que las pruebas pasen.

## Paso 4: Ejecutar las pruebas de nuevo

```bash
cd service && ./vendor/bin/phpunit
```

Esperado: Las pruebas pasan (verde).

## Paso 5: Verificación completa

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Paso 6: Commit

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Casos de prueba existentes (20 pruebas / 41 aserciones)

| Clase de prueba | Pruebas | Cubre |
|-----------|-------|--------|
| FieldMappingTest | 5 | mapeo de campo/estado, transformadores, entrada vacía, desconocido→extra |
| HashidsServiceTest | 5 | round trip encode/decode, unicidad, manejo de hashes inválidos |
| ReportBuilderTest | 3 | fórmulas SQL de métricas, filtrado por dimensión, métricas derivadas |
| CampaignDataTest | 3 | fromArray, valores por defecto, campos extra |
| AdapterRegistryTest | 4 | register/get/has/all, inexistente devuelve null |

## Lista de verificación pre-commit

- [ ] `./vendor/bin/phpunit` pasa
- [ ] `npx vue-tsc --noEmit` pasa
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` pasa
- [ ] Cabecera de Copyright en todos los archivos nuevos
- [ ] Sin `getenv()` — usa `env()` en su lugar
- [ ] Los nombres de tabla usan el prefijo `ads_`
- [ ] Sin `\` inicial en las clases globales
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` en archivos con namespace
