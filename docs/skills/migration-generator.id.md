# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

Buat file migrasi SQL mengikuti konvensi proyek.

## Aturan

1. **Prefiks tabel**: `erik_` untuk semua tabel yang menghadap pengguna, `admin_` untuk tabel panel admin
2. **Primary key**: `BIGINT UNSIGNED PRIMARY KEY` — TANPA AUTO_INCREMENT, gunakan Snowflake ID
3. **Engine**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Kolom uang**: `BIGINT DEFAULT 0` untuk sen (分) — konsisten dengan model terpadu
5. **Timestamp**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **Field JSON**: `JSON NULL` untuk data yang dapat diperluas
7. **Indeks**: Tambahkan untuk semua kolom filter/join yang digunakan dalam kueri

## Template

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

## Data Seed (opsional)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## Lokasi File

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Memperbarui Tabel yang Ada

Gunakan `ALTER TABLE` dengan pemeriksaan `IF NOT EXISTS`. Jangan drop kolom — gunakan depresiasi lunak.
