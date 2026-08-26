# مولّد الهجرات (Migration Generator)

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

توليد ملفات هجرة SQL وفقًا لاصطلاحات المشروع.

## القواعد

1. **بادئة الجداول**: `erik_` لجميع الجداول الموجهة للمستخدمين، و`admin_` لجداول لوحة الإدارة
2. **المفتاح الأساسي**: `BIGINT UNSIGNED PRIMARY KEY` — بدون AUTO_INCREMENT، استخدم معرّف Snowflake
3. **محرك التخزين**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **أعمدة المال**: `BIGINT DEFAULT 0` للفين (分) — بما يتوافق مع النموذج الموحد
5. **الطوابع الزمنية**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **حقول JSON**: `JSON NULL` للبيانات القابلة للتوسيع
7. **الفهارس**: إضافتها لجميع أعمدة التصفية/الانضمام المستخدمة في الاستعلامات

## القالب

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

## بيانات البذور (اختياري)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## موقع الملف

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## تحديث الجداول الموجودة

استخدم `ALTER TABLE` مع فحوصات `IF NOT EXISTS`. لا تحذف الأعمدة — استخدم الإهمال الناعم.
