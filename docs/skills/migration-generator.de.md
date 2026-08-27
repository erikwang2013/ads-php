# Migrations-Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

SQL-Migrationsdateien gemäß den Projektkonventionen generieren.

## Regeln

1. **Tabellenpräfix**: `ads_` für alle nutzerseitigen Tabellen, `admin_` für Admin-Panel-Tabellen
2. **Primärschlüssel**: `BIGINT UNSIGNED PRIMARY KEY` — KEIN AUTO_INCREMENT, Snowflake-ID verwenden
3. **Engine**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Geldspalten**: `BIGINT DEFAULT 0` für Fen (分) — konsistent mit dem vereinheitlichten Modell
5. **Zeitstempel**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON-Felder**: `JSON NULL` für erweiterbare Daten
7. **Indizes**: Für alle Filter-/Join-Spalten hinzufügen, die in Abfragen verwendet werden

## Vorlage

```sql
CREATE TABLE IF NOT EXISTS `ads_table_name` (
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

## Seed-Daten (optional)

```sql
INSERT INTO ads_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## Dateiablage

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Bestehende Tabellen aktualisieren

`ALTER TABLE` mit `IF NOT EXISTS`-Prüfungen verwenden. Keine Spalten droppen — sanfte Abkündigung (soft deprecation) verwenden.
