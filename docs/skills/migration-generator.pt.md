# Gerador de Migrações

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

Gere arquivos de migração SQL seguindo as convenções do projeto.

## Regras

1. **Prefixo de tabela**: `erik_` para todas as tabelas voltadas ao usuário, `admin_` para tabelas do painel administrativo
2. **Chave primária**: `BIGINT UNSIGNED PRIMARY KEY` — SEM AUTO_INCREMENT, use ID Snowflake
3. **Engine**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Colunas de dinheiro**: `BIGINT DEFAULT 0` para fen (分) — consistente com o modelo unificado
5. **Timestamps**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **Campos JSON**: `JSON NULL` para dados extensíveis
7. **Índices**: Adicione para todas as colunas de filtro/join usadas nas consultas

## Modelo

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

## Dados de seed (opcional)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## Localização do arquivo

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Atualizando tabelas existentes

Use `ALTER TABLE` com verificações `IF NOT EXISTS`. Não remova colunas — use depreciação suave.
