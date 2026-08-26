# Erik Stack Integration

[中文](docs/skills/erik-stack.md) | [English](docs/skills/erik-stack.en.md) | [한국어](docs/skills/erik-stack.ko.md) | [Русский](docs/skills/erik-stack.ru.md) | [Deutsch](docs/skills/erik-stack.de.md) | [Français](docs/skills/erik-stack.fr.md) | [Español](docs/skills/erik-stack.es.md) | [Português](docs/skills/erik-stack.pt.md) | [हिन्दी](docs/skills/erik-stack.hi.md) | [العربية](docs/skills/erik-stack.ar.md) | [বাংলা](docs/skills/erik-stack.bn.md) | [Bahasa Indonesia](docs/skills/erik-stack.id.md) | [日本語](docs/skills/erik-stack.ja.md)

इस प्रोजेक्ट में 7 Erik Stack पैकेज का उपयोग कैसे करें।

## पैकेज और उपयोग

### snowflake-php — वितरित ID जनरेशन
```php
use erik\support\SnowflakeTrait;

class YourModel extends Model
{
    use SnowflakeTrait;
    // Auto-generates BIGINT snowflake ID on creating event
    // No AUTO_INCREMENT needed in SQL
}
```

### hashids — API ID एन्क्रिप्शन
```php
use erik\support\HashidsService;

// In ApiResponse: automatically encodes id/*_id fields
ApiResponse::success($data, 'success', encodeIds: true);

// Manual usage
$service = new HashidsService();
$encoded = $service->encode(123456789);
$decoded = $service->decode($encoded);
```

### jwt-webman — JWT प्रमाणीकरण
```php
use Erikwang2013\JwtWebman\Jwt;

// Encode token
$token = Jwt::encode(['uid' => $userId, 'tid' => $tenantId]);

// Verify token (in middleware)
$payload = Jwt::verify($token);
$request->userId = $payload['uid'];
```

### encryption — API-स्तरीय एन्क्रिप्शन
ग्लोबल मिडलवेयर के माध्यम से सक्षम। क्लाइंट `X-Encrypted: 1` header भेजते हैं।
अनुरोध बॉडी स्वचालित रूप से डिक्रिप्ट होती है, प्रतिक्रिया बॉडी स्वचालित रूप से एन्क्रिप्ट होती है।
```env
APP_ENCRYPTION_KEY=your-32-char-key-here
```

### encryptable — डेटाबेस फ़ील्ड एन्क्रिप्शन
```php
use Erikwang2013\Encryptable\Encryptable;

class PlatformAccount extends Model
{
    use Encryptable;
    protected array $encryptable = ['access_token', 'refresh_token'];
    // These fields are auto-encrypted before DB write, decrypted after read
}
```

### webman-scout — Elasticsearch सिंक
`service/config/scout.php` में कॉन्फ़िगर किया गया। `Searchable` trait वाले Models स्वचालित रूप से ES से सिंक होते हैं।
```env
SCOUT_DRIVER=elasticsearch
ES_HOST=127.0.0.1:9200
```

### season — देश ध्वज
`PlatformBadge.vue` में Unicode ध्वज emoji के माध्यम से उपयोग होता है। रनटाइम इम्पोर्ट की आवश्यकता नहीं।
```typescript
const flagMap: Record<string, string> = {
  juliang: '🇨🇳', google: '🇺🇸', tiktok: '🇸🇬', spotify: '🇸🇪',
};
```

## एनवायरनमेंट वेरिएबल्स
```
HASHIDS_SALT=ads-platform-salt
HASHIDS_MIN_LENGTH=8
APP_ENCRYPTION_KEY=your-32-char-encryption-key-here
SCOUT_DRIVER=elasticsearch
ES_HOST=127.0.0.1:9200
ES_INDEX=ads_platform
JULIANG_APP_ID=
JULIANG_SECRET=
BAIDU_APP_ID=
# ... one per platform
```
