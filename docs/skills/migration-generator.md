# Migration Generator

Generate SQL migration files following project conventions.

## Rules

1. **Table prefix**: `erik_` for all user-facing tables, `admin_` for admin panel tables
2. **Primary key**: `BIGINT UNSIGNED PRIMARY KEY` — NO AUTO_INCREMENT, use Snowflake ID
3. **Engine**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Money columns**: `BIGINT DEFAULT 0` for fen (分) — consistent with unified model
5. **Timestamps**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON fields**: `JSON NULL` for extensible data
7. **Indexes**: Add for all filter/join columns used in queries

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

## Seed data (optional)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## File location

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Updating existing tables

Use `ALTER TABLE` with `IF NOT EXISTS` checks. Do not drop columns — use soft deprecation.
