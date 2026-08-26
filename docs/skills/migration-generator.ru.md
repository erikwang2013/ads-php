# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

Генерация SQL-файлов миграций по конвенциям проекта.

## Правила

1. **Префикс таблиц**: `erik_` для всех пользовательских таблиц, `admin_` для таблиц админ-панели
2. **Первичный ключ**: `BIGINT UNSIGNED PRIMARY KEY` — БЕЗ AUTO_INCREMENT, используйте Snowflake ID
3. **Движок**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Денежные столбцы**: `BIGINT DEFAULT 0` для фэней (分) — в соответствии с унифицированной моделью
5. **Метки времени**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON-поля**: `JSON NULL` для расширяемых данных
7. **Индексы**: добавляйте для всех столбцов фильтрации/объединения, используемых в запросах

## Шаблон

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

## Seed-данные (опционально)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## Расположение файла

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Обновление существующих таблиц

Используйте `ALTER TABLE` с проверками `IF NOT EXISTS`. Не удаляйте столбцы — используйте мягкое устаревание (soft deprecation).
