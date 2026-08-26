# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

プロジェクト規約に従って SQL マイグレーションファイルを生成します。

## Rules

1. **Table prefix**: ユーザー向けテーブルはすべて `erik_`、管理パネルのテーブルは `admin_`
2. **Primary key**: `BIGINT UNSIGNED PRIMARY KEY` — AUTO_INCREMENT なし、Snowflake ID を使用
3. **Engine**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Money columns**: 分 (fen) 単位は `BIGINT DEFAULT 0` — 統一モデルと一致
5. **Timestamps**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON fields**: 拡張可能データは `JSON NULL`
7. **Indexes**: クエリで使用するすべてのフィルタ/結合カラムに追加

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

## Seed data (任意)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## ファイルの場所

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## 既存テーブルの更新

`IF NOT EXISTS` チェック付きの `ALTER TABLE` を使用します。カラムは削除せず、ソフト非推奨化を使用します。
