# TDD Workflow

[中文](docs/skills/tdd-workflow.md) | [English](docs/skills/tdd-workflow.en.md) | [한국어](docs/skills/tdd-workflow.ko.md) | [Русский](docs/skills/tdd-workflow.ru.md) | [Deutsch](docs/skills/tdd-workflow.de.md) | [Français](docs/skills/tdd-workflow.fr.md) | [Español](docs/skills/tdd-workflow.es.md) | [Português](docs/skills/tdd-workflow.pt.md) | [हिन्दी](docs/skills/tdd-workflow.hi.md) | [العربية](docs/skills/tdd-workflow.ar.md) | [বাংলা](docs/skills/tdd-workflow.bn.md) | [Bahasa Indonesia](docs/skills/tdd-workflow.id.md) | [日本語](docs/skills/tdd-workflow.ja.md)

이 프로젝트의 TDD(테스트 주도 개발) 검증 절차.

## 1단계: 테스트 작성/수정

테스트는 `service/tests/Unit/`에 있습니다. `PHPUnit\Framework\TestCase`를 상속한 테스트 클래스를 생성합니다.

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

## 2단계: 테스트 실행

```bash
cd service && ./vendor/bin/phpunit
```

예상: 테스트 실패 (red).

## 3단계: 구현

테스트가 통과하도록 최소 코드 작성.

## 4단계: 테스트 다시 실행

```bash
cd service && ./vendor/bin/phpunit
```

예상: 테스트 통과 (green).

## 5단계: 전체 검증

```bash
# PHP 문법 검사 (전체 파일)
find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax"

# TypeScript 타입 검사
cd admin/public/web && npx vue-tsc --noEmit

# 전체 테스트
cd service && ./vendor/bin/phpunit --testdox
```

## 6단계: 커밋

```bash
git add service/tests/ service/plugin/ && git commit -m "feat: add feature X with tests"
```

## 기존 테스트 케이스 (20 테스트 / 41 어서션)

| 테스트 클래스 | 테스트 수 | 커버 범위 |
|-----------|-------|--------|
| FieldMappingTest | 5 | 필드/상태 매핑, 트랜스포머, 빈 입력, unknown→extra |
| HashidsServiceTest | 5 | encode/decode 왕복, 고유성, 잘못된 hash 처리 |
| ReportBuilderTest | 3 | 지표 SQL 수식, 차원 필터링, 파생 지표 |
| CampaignDataTest | 3 | fromArray, 기본값, extra 필드 |
| AdapterRegistryTest | 4 | register/get/has/all, 없는 항목은 null 반환 |

## 커밋 전 체크리스트

- [ ] `./vendor/bin/phpunit` 통과
- [ ] `npx vue-tsc --noEmit` 통과
- [ ] `find service -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` 통과
- [ ] 모든 새 파일에 Copyright 헤더
- [ ] `getenv()` 금지 — `env()` 사용
- [ ] 테이블 이름은 `erik_` 접두사
- [ ] 전역 클래스에 앞 `\` 없음
- [ ] 네임스페이스 파일에 `use Throwable;` / `use RuntimeException;` / `use InvalidArgumentException;`
