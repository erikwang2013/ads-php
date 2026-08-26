# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

Générer des fichiers de migration SQL en suivant les conventions du projet.

## Règles

1. **Préfixe de table** : `erik_` pour toutes les tables destinées aux utilisateurs, `admin_` pour les tables du panneau d'administration
2. **Clé primaire** : `BIGINT UNSIGNED PRIMARY KEY` — PAS d'AUTO_INCREMENT, utiliser l'ID Snowflake
3. **Moteur** : `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **Colonnes d'argent** : `BIGINT DEFAULT 0` pour les fen (分) — cohérent avec le modèle unifié
5. **Horodatages** : `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **Champs JSON** : `JSON NULL` pour les données extensibles
7. **Index** : Ajouter pour toutes les colonnes de filtrage/joindre utilisées dans les requêtes

## Modèle

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

## Données de départ (optionnel)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## Emplacement du fichier

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## Mise à jour de tables existantes

Utiliser `ALTER TABLE` avec des vérifications `IF NOT EXISTS`. Ne pas supprimer de colonnes — utiliser une dépréciation douce.
