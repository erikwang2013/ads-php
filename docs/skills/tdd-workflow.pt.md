# Fluxo de Trabalho TDD

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

Procedimento de verificação de Desenvolvimento Orientado a Testes para este projeto.

## Etapa 1: Escrever/Atualizar o Teste

Os testes ficam em `service/tests/Unit/`. Crie uma classe de teste que estenda `PHPUnit\Framework\TestCase`.

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

## Etapa 2: Executar os Testes

```bash
cd service && ./vendor/bin/phpunit
```

Esperado: Os testes falham (vermelho).

## Etapa 3: Implementar

Escreva o código mínimo para fazer os testes passarem.

## Etapa 4: Executar os Testes Novamente

```bash
cd service && ./vendor/bin/phpunit
```

Esperado: Os testes passam (verde).

## Etapa 5: Verificação Completa

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Etapa 6: Commit

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Casos de Teste Existentes (20 testes / 41 asserções)

| Classe de Teste | Testes | Cobre |
|-----------------|--------|-------|
| FieldMappingTest | 5 | mapeamento de campo/status, transformadores, entrada vazia, desconhecido→extra |
| HashidsServiceTest | 5 | ida e volta encode/decode, unicidade, tratamento de hash inválido |
| ReportBuilderTest | 3 | fórmulas SQL de métricas, filtragem por dimensão, métricas derivadas |
| CampaignDataTest | 3 | fromArray, padrões, campos extras |
| AdapterRegistryTest | 4 | register/get/has/all, inexistente retorna null |

## Checklist Pré-commit

- [ ] `./vendor/bin/phpunit` passa
- [ ] `npx vue-tsc --noEmit` passa
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` passa
- [ ] Header de Copyright em todos os arquivos novos
- [ ] Sem `getenv()` — use `env()` em vez disso
- [ ] Nomes de tabela usam o prefixo `ads_`
- [ ] Sem `\` inicial em classes globais
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` em arquivos com namespace
