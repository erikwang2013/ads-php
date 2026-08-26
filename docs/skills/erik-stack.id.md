# Integrasi Erik Stack

[中文](docs/skills/erik-stack.md) | [English](docs/skills/erik-stack.en.md) | [한국어](docs/skills/erik-stack.ko.md) | [Русский](docs/skills/erik-stack.ru.md) | [Deutsch](docs/skills/erik-stack.de.md) | [Français](docs/skills/erik-stack.fr.md) | [Español](docs/skills/erik-stack.es.md) | [Português](docs/skills/erik-stack.pt.md) | [हिन्दी](docs/skills/erik-stack.hi.md) | [العربية](docs/skills/erik-stack.ar.md) | [বাংলা](docs/skills/erik-stack.bn.md) | [Bahasa Indonesia](docs/skills/erik-stack.id.md) | [日本語](docs/skills/erik-stack.ja.md)

Cara menggunakan 7 paket Erik Stack di proyek ini.

## Paket & Penggunaan

### snowflake-php — Generasi ID Terdistribusi
```php
use erik\support\SnowflakeTrait;

class YourModel extends Model
{
    use SnowflakeTrait;
    // Auto-generates BIGINT snowflake ID on creating event
    // No AUTO_INCREMENT needed in SQL
}
```

### hashids — Enkripsi ID API
```php
use erik\support\HashidsService;

// In ApiResponse: automatically encodes id/*_id fields
ApiResponse::success($data, 'success', encodeIds: true);

// Manual usage
$service = new HashidsService();
$encoded = $service->encode(123456789);
$decoded = $service->decode($encoded);
```

### jwt-webman — Autentikasi JWT
```php
use Erikwang2013\JwtWebman\Jwt;

// Encode token
$token = Jwt::encode(['uid' => $userId, 'tid' => $tenantId]);

// Verify token (in middleware)
$payload = Jwt::verify($token);
$request->userId = $payload['uid'];
```

### encryption — Enkripsi Tingkat API
Diaktifkan melalui middleware global. Klien mengirim header `X-Encrypted: 1`.
Body request otomatis didekripsi, body respons otomatis dienkripsi.
```env
APP_ENCRYPTION_KEY=your-32-char-key-here
```

### encryptable — Enkripsi Field Database
```php
use Erikwang2013\Encryptable\Encryptable;

class PlatformAccount extends Model
{
    use Encryptable;
    protected array $encryptable = ['access_token', 'refresh_token'];
    // These fields are auto-encrypted before DB write, decrypted after read
}
```

### webman-scout — Sinkronisasi Elasticsearch
Dikonfigurasi di `service/config/scout.php`. Model dengan trait `Searchable` otomatis sinkron ke ES.
```env
SCOUT_DRIVER=elasticsearch
ES_HOST=127.0.0.1:9200
```

### season — Bendera Negara
Digunakan di `PlatformBadge.vue` melalui emoji bendera Unicode. Tidak perlu import saat runtime.
```typescript
const flagMap: Record<string, string> = {
  juliang: '🇨🇳', google: '🇺🇸', tiktok: '🇸🇬', spotify: '🇸🇪',
};
```

## Variabel Lingkungan
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
