# Migration Generator

[中文](docs/skills/migration-generator.md) | [English](docs/skills/migration-generator.en.md) | [한국어](docs/skills/migration-generator.ko.md) | [Русский](docs/skills/migration-generator.ru.md) | [Deutsch](docs/skills/migration-generator.de.md) | [Français](docs/skills/migration-generator.fr.md) | [Español](docs/skills/migration-generator.es.md) | [Português](docs/skills/migration-generator.pt.md) | [हिन्दी](docs/skills/migration-generator.hi.md) | [العربية](docs/skills/migration-generator.ar.md) | [বাংলা](docs/skills/migration-generator.bn.md) | [Bahasa Indonesia](docs/skills/migration-generator.id.md) | [日本語](docs/skills/migration-generator.ja.md)

प्रोजेक्ट परंपराओं का पालन करते हुए SQL माइग्रेशन फ़ाइलें जनरेट करें।

## नियम

1. **टेबल प्रीफ़िक्स**: सभी उपयोगकर्ता-मुखी टेबलों के लिए `erik_`, एडमिन पैनल टेबलों के लिए `admin_`
2. **प्राइमरी की**: `BIGINT UNSIGNED PRIMARY KEY` — कोई AUTO_INCREMENT नहीं, Snowflake ID का उपयोग करें
3. **इंजन**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
4. **पैसा कॉलम**: fen (分) के लिए `BIGINT DEFAULT 0` — एकीकृत मॉडल के अनुरूप
5. **टाइमस्टैम्प**: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` + `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
6. **JSON फ़ील्ड**: एक्सटेंसिबल डेटा के लिए `JSON NULL`
7. **इंडेक्स**: क्वेरी में उपयोग होने वाले सभी फ़िल्टर/जॉइन कॉलम के लिए जोड़ें

## टेम्पलेट

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

## सीड डेटा (वैकल्पिक)

```sql
INSERT INTO erik_table_name (id, name) VALUES (1, '默认数据')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## फ़ाइल स्थान

`service/plugin/ads-{module}/migration/create_{tables}.sql`

## मौजूदा टेबल अपडेट करना

`IF NOT EXISTS` जाँच के साथ `ALTER TABLE` का उपयोग करें। कॉलम ड्रॉप न करें — सॉफ्ट डिप्रिकेशन का उपयोग करें।
