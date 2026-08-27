# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

প্রজেক্ট কনভেনশন অনুসরণ করে SQL মাইগ্রেশন ফাইল তৈরি করুন।

## নিয়ম

1. **টেবিল প্রিফিক্স**: সব ইউজার-ফেসিং টেবিলে `ads_`，অ্যাডমিন প্যানেল টেবিলে `admin_`
2. **প্রাইমারি কী**: `BIGINT UNSIGNED PRIMARY KEY` — NO AUTO_INCREMENT, Snowflake ID ব্যবহার করুন
3. **ইঞ্জিন**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **টাকার কলাম**: ফেন (分)-এর জন্য `BIGINT DEFAULT 0` — ইউনিফাইড মডেলের সাথে সামঞ্জস্যপূর্ণ
5. **টাইমস্ট্যাম্প**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON ফিল্ড**: এক্সটেনসিবল ডেটার জন্য `JSON NULL`
7. **ইনডেক্স**: কোয়েরিতে ব্যবহৃত সব ফিল্টার/জয়েন কলামের জন্য যোগ করুন

## টেমপ্লেট

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

## সিড ডেটা (ঐচ্ছিক)

```sql
INSERT INTO ads_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## ফাইল অবস্থান

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## বিদ্যমান টেবিল আপডেট

`IF NOT EXISTS` চেক সহ `ALTER TABLE` ব্যবহার করুন। কলাম ড্রপ করবেন না — সফট ডিপ্রিকেশন ব্যবহার করুন।
