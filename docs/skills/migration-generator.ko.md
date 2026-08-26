# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

프로젝트 규약에 따라 SQL 마이그레이션 파일을 생성합니다.

## 규칙

1. **테이블 접두사**: 모든 사용자 대상 테이블은 `erik_`, 관리자 패널 테이블은 `admin_`
2. **기본 키**: `BIGINT UNSIGNED PRIMARY KEY` — AUTO_INCREMENT 없음, Snowflake ID 사용
3. **엔진**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **금액 컬럼**: 분(分) 단위 `BIGINT DEFAULT 0` — 통일 모델과 일관성
5. **타임스탬프**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON 필드**: 확장 가능한 데이터는 `JSON NULL`
7. **인덱스**: 쿼리에 사용되는 모든 필터/조인 컬럼에 추가

## 템플릿

```sql
CREATE TABLE IF NOT EXISTS `erik_table_name` (
    `id` BIGINT UNSIGNED PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT DEFAULT 1,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_status` (`tenant_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 시드 데이터 (선택 사항)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## 파일 위치

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## 기존 테이블 업데이트

`IF NOT EXISTS` 체크와 함께 `ALTER TABLE` 사용. 컬럼을 삭제하지 마세요 — 소프트 폐기를 사용하세요.
