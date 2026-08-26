# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

Genera archivos de migración SQL siguiendo las convenciones del proyecto.

## Reglas

1. **Prefijo de tabla**: `erik_` para todas las tablas orientadas al usuario, `admin_` para las tablas del panel de administración
2. **Clave primaria**: `BIGINT UNSIGNED PRIMARY KEY` — SIN AUTO_INCREMENT, usa ID Snowflake
3. **Motor**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Columnas de dinero**: `BIGINT DEFAULT 0` para fen (分) — consistente con el modelo unificado
5. **Marcas de tiempo**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **Campos JSON**: `JSON NULL` para datos extensibles
7. **Índices**: Añade para todas las columnas de filtro/join usadas en consultas

## Plantilla

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

## Datos semilla (opcional)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## Ubicación del archivo

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Actualizar tablas existentes

Usa `ALTER TABLE` con comprobaciones `IF NOT EXISTS`. No elimines columnas — usa deprecación suave.
