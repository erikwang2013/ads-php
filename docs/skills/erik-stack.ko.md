# Erik Stack Integration

[中文](docs/skills/erik-stack.md) | [English](docs/skills/erik-stack.en.md) | [한국어](docs/skills/erik-stack.ko.md) | [Русский](docs/skills/erik-stack.ru.md) | [Deutsch](docs/skills/erik-stack.de.md) | [Français](docs/skills/erik-stack.fr.md) | [Español](docs/skills/erik-stack.es.md) | [Português](docs/skills/erik-stack.pt.md) | [हिन्दी](docs/skills/erik-stack.hi.md) | [العربية](docs/skills/erik-stack.ar.md) | [বাংলা](docs/skills/erik-stack.bn.md) | [Bahasa Indonesia](docs/skills/erik-stack.id.md) | [日本語](docs/skills/erik-stack.ja.md)

이 프로젝트에서 7개 Erik Stack 패키지를 사용하는 방법.

## 패키지 및 사용법

### snowflake-php — 분산 ID 생성
```php
use erik\support\SnowflakeTrait;

class YourModel extends Model
{
    use SnowflakeTrait;
    // Auto-generates BIGINT snowflake ID on creating event
    // No AUTO_INCREMENT needed in SQL
}
```

### hashids — API ID 암호화
```php
use erik\support\HashidsService;

// ApiResponse에서: id/*_id 필드를 자동 인코딩
ApiResponse::success($data, 'success', encodeIds: true);

// 수동 사용
$service = new HashidsService();
$encoded = $service->encode(123456789);
$decoded = $service->decode($encoded);
```

### jwt-webman — JWT 인증
```php
use Erikwang2013\JwtWebman\Jwt;

// Token 인코딩
$token = Jwt::encode(['uid' => $userId, 'tid' => $tenantId]);

// Token 검증 (미들웨어에서)
$payload = Jwt::verify($token);
$request->userId = $payload['uid'];
```

### encryption — API 계층 암호화
글로벌 미들웨어를 통해 활성화. 클라이언트는 `X-Encrypted: 1` 헤더를 전송.
요청 본문은 자동 복호화, 응답 본문은 자동 암호화.
```env
APP_ENCRYPTION_KEY=your-32-char-key-here
```

### encryptable — 데이터베이스 필드 암호화
```php
use Erikwang2013\Encryptable\Encryptable;

class PlatformAccount extends Model
{
    use Encryptable;
    protected array $encryptable = ['access_token', 'refresh_token'];
    // These fields are auto-encrypted before DB write, decrypted after read
}
```

### webman-scout — Elasticsearch 동기화
`service/config/scout.php`에서 구성. `Searchable` trait를 가진 모델이 ES에 자동 동기화.
```env
SCOUT_DRIVER=elasticsearch
ES_HOST=127.0.0.1:9200
```

### season — 국가 국기
`PlatformBadge.vue`에서 Unicode 국기 이모지로 사용. 런타임 import 불필요.
```typescript
const flagMap: Record<string, string> = {
  juliang: '🇨🇳', google: '🇺🇸', tiktok: '🇸🇬', spotify: '🇸🇪',
};
```

## 환경 변수
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
