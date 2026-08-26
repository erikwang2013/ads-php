# Erik Stack Integration

[中文](docs/skills/erik-stack.md) | [English](docs/skills/erik-stack.en.md) | [한국어](docs/skills/erik-stack.ko.md) | [Русский](docs/skills/erik-stack.ru.md) | [Deutsch](docs/skills/erik-stack.de.md) | [Français](docs/skills/erik-stack.fr.md) | [Español](docs/skills/erik-stack.es.md) | [Português](docs/skills/erik-stack.pt.md) | [हिन्दी](docs/skills/erik-stack.hi.md) | [العربية](docs/skills/erik-stack.ar.md) | [বাংলা](docs/skills/erik-stack.bn.md) | [Bahasa Indonesia](docs/skills/erik-stack.id.md) | [日本語](docs/skills/erik-stack.ja.md)

এই প্রজেক্টে 7টি Erik Stack প্যাকেজ কীভাবে ব্যবহার করবেন।

## প্যাকেজ ও ব্যবহার

### snowflake-php — ডিস্ট্রিবিউটেড ID জেনারেশন
```php
use erik\support\SnowflakeTrait;

class YourModel extends Model
{
    use SnowflakeTrait;
    // Auto-generates BIGINT snowflake ID on creating event
    // No AUTO_INCREMENT needed in SQL
}
```

### hashids — API ID এনক্রিপশন
```php
use erik\support\HashidsService;

// In ApiResponse: automatically encodes id/*_id fields
ApiResponse::success($data, 'success', encodeIds: true);

// Manual usage
$service = new HashidsService();
$encoded = $service->encode(123456789);
$decoded = $service->decode($encoded);
```

### jwt-webman — JWT অথেনটিকেশন
```php
use Erikwang2013\JwtWebman\Jwt;

// Encode token
$token = Jwt::encode(['uid' => $userId, 'tid' => $tenantId]);

// Verify token (in middleware)
$payload = Jwt::verify($token);
$request->userId = $payload['uid'];
```

### encryption — API-লেভেল এনক্রিপশন
গ্লোবাল মিডলওয়্যারের মাধ্যমে সক্রিয় হয়। ক্লায়েন্টরা `X-Encrypted: 1` header পাঠায়।
রিকোয়েস্ট বডি অটো ডিক্রিপ্ট হয়, রেসপন্স বডি অটো এনক্রিপ্ট হয়।
```env
APP_ENCRYPTION_KEY=your-32-char-key-here
```

### encryptable — ডেটাবেস ফিল্ড এনক্রিপশন
```php
use Erikwang2013\Encryptable\Encryptable;

class PlatformAccount extends Model
{
    use Encryptable;
    protected array $encryptable = ['access_token', 'refresh_token'];
    // These fields are auto-encrypted before DB write, decrypted after read
}
```

### webman-scout — Elasticsearch সিঙ্ক
`service/config/scout.php`-এ কনফিগারড। `Searchable` trait সহ মডেলগুলো অটো ES-তে সিঙ্ক হয়।
```env
SCOUT_DRIVER=elasticsearch
ES_HOST=127.0.0.1:9200
```

### season — দেশের ফ্ল্যাগ
`PlatformBadge.vue`-এ Unicode ফ্ল্যাগ emoji-র মাধ্যমে ব্যবহৃত হয়। রানটাইম ইমপোর্ট প্রয়োজন নেই।
```typescript
const flagMap: Record<string, string> = {
  juliang: '🇨🇳', google: '🇺🇸', tiktok: '🇸🇬', spotify: '🇸🇪',
};
```

## এনভায়রনমেন্ট ভ্যারিয়েবল
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
