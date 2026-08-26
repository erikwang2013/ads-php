# Alur Kerja TDD

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

Prosedur verifikasi Test-Driven Development untuk proyek ini.

## Langkah 1: Tulis/Perbarui Test

Test berada di `service/tests/Unit/`. Buat kelas test yang memperluas `PHPUnit\Framework\TestCase`.

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

## Langkah 2: Jalankan Test

```bash
cd service && ./vendor/bin/phpunit
```

Ekspektasi: Test gagal (merah).

## Langkah 3: Implementasikan

Tulis kode minimal agar test lulus.

## Langkah 4: Jalankan Test Lagi

```bash
cd service && ./vendor/bin/phpunit
```

Ekspektasi: Test lulus (hijau).

## Langkah 5: Verifikasi Lengkap

```bash
# PHP syntax check (all files)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript type check
cd admin/public/web && npx vue-tsc --noEmit

# All tests
cd service && ./vendor/bin/phpunit --testdox
```

## Langkah 6: Commit

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## Kasus Test yang Ada (20 test / 41 assertion)

| Kelas Test | Test | Cakupan |
|-----------|-------|--------|
| FieldMappingTest | 5 | pemetaan field/status, transformer, input kosong, unknown→extra |
| HashidsServiceTest | 5 | encode/decode round trip, keunikan, penanganan hash tidak valid |
| ReportBuilderTest | 3 | formula SQL metrik, filter dimensi, metrik turunan |
| CampaignDataTest | 3 | fromArray, default, field extra |
| AdapterRegistryTest | 4 | register/get/has/all, tidak ada mengembalikan null |

## Checklist Pra-Commit

- [ ] `./vendor/bin/phpunit` lulus
- [ ] `npx vue-tsc --noEmit` lulus
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` lulus
- [ ] Header Copyright di semua file baru
- [ ] Tanpa `getenv()` — gunakan `env()` sebagai gantinya
- [ ] Nama tabel menggunakan prefiks `erik_`
- [ ] Tanpa `\` di awal kelas global
- [ ] `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;` di file ber-namespace
