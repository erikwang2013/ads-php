# Erik Stack Integration

[中文](docs/skills/erik-stack.md) | [English](docs/skills/erik-stack.en.md) | [한국어](docs/skills/erik-stack.ko.md) | [Русский](docs/skills/erik-stack.ru.md) | [Deutsch](docs/skills/erik-stack.de.md) | [Français](docs/skills/erik-stack.fr.md) | [Español](docs/skills/erik-stack.es.md) | [Português](docs/skills/erik-stack.pt.md) | [हिन्दी](docs/skills/erik-stack.hi.md) | [العربية](docs/skills/erik-stack.ar.md) | [বাংলা](docs/skills/erik-stack.bn.md) | [Bahasa Indonesia](docs/skills/erik-stack.id.md) | [日本語](docs/skills/erik-stack.ja.md)

Как использовать 7 пакетов Erik Stack в этом проекте.

## Пакеты и использование

### snowflake-php — Распределенная генерация ID
```php
use erik\support\SnowflakeTrait;

class YourModel extends Model
{
    use SnowflakeTrait;
    // Auto-generates BIGINT snowflake ID on creating event
    // No AUTO_INCREMENT needed in SQL
}
```

### hashids — Шифрование ID в API
```php
use erik\support\HashidsService;

// In ApiResponse: automatically encodes id/*_id fields
ApiResponse::success($data, 'success', encodeIds: true);

// Manual usage
$service = new HashidsService();
$encoded = $service->encode(123456789);
$decoded = $service->decode($encoded);
```

### jwt-webman — JWT-аутентификация
```php
use Erikwang2013\JwtWebman\Jwt;

// Encode token
$token = Jwt::encode(['uid' => $userId, 'tid' => $tenantId]);

// Verify token (in middleware)
$payload = Jwt::verify($token);
$request->userId = $payload['uid'];
```

### encryption — Шифрование на уровне API
Включается через глобальный middleware. Клиенты отправляют заголовок `X-Encrypted: 1`.
Тело запроса автоматически расшифровывается, тело ответа автоматически шифруется.
```env
APP_ENCRYPTION_KEY=your-32-char-key-here
```

### encryptable — Шифрование полей БД
```php
use Erikwang2013\Encryptable\Encryptable;

class PlatformAccount extends Model
{
    use Encryptable;
    protected array $encryptable = ['access_token', 'refresh_token'];
    // These fields are auto-encrypted before DB write, decrypted after read
}
```

### webman-scout — Синхронизация Elasticsearch
Настраивается в `service/config/scout.php`. Модели с trait `Searchable` автоматически синхронизируются с ES.
```env
SCOUT_DRIVER=elasticsearch
ES_HOST=127.0.0.1:9200
```

### season — Флаги стран
Используется в `PlatformBadge.vue` через Unicode-флаги-эмодзи. Импорт в рантайме не требуется.
```typescript
const flagMap: Record<string, string> = {
  juliang: '🇨🇳', google: '🇺🇸', tiktok: '🇸🇬', spotify: '🇸🇪',
};
```

## Переменные окружения
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
