# Workflow TDD

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

Procédure de vérification en développement piloté par les tests pour ce projet.

## Étape 1 : Écrire/Mettre à jour le test

Les tests vivent dans `service/tests/Unit/`. Créer une classe de test étendant `PHPUnit\Framework\TestCase`.

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

## Étape 2 : Exécuter les tests

```bash
cd service && ./vendor/bin/phpunit
```

Attendu : les tests échouent (rouge).

## Étape 3 : Implémenter

Écrire le code minimal pour que les tests passent.

## Étape 4 : Réexécuter les tests

```bash
cd service && ./vendor/bin/phpunit
```

Attendu : les tests passent (vert).

## Étape 5 : Vérification complète

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Étape 6 : Commit

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Cas de test existants (20 tests / 41 assertions)

| Classe de test | Tests | Couvre |
|-----------|-------|--------|
| FieldMappingTest | 5 | mapping champ/statut, transformateurs, entrée vide, inconnu→extra |
| HashidsServiceTest | 5 | aller-retour encode/decode, unicité, gestion de hash invalide |
| ReportBuilderTest | 3 | formules SQL des métriques, filtrage par dimension, métriques dérivées |
| CampaignDataTest | 3 | fromArray, valeurs par défaut, champs extra |
| AdapterRegistryTest | 4 | register/get/has/all, inexistant renvoie null |

## Liste de contrôle avant commit

- [ ] `./vendor/bin/phpunit` passe
- [ ] `npx vue-tsc --noEmit` passe
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` passe
- [ ] En-tête de copyright sur tous les nouveaux fichiers
- [ ] Pas de `getenv()` — utiliser `env()` à la place
- [ ] Les noms de tables utilisent le préfixe `ads_`
- [ ] Pas de `\` initial sur les classes globales
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` dans les fichiers avec namespace
