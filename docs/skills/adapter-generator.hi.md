# Adapter Generator

[中文](docs/skills/adapter-generator.md) | [English](docs/skills/adapter-generator.en.md) | [한국어](docs/skills/adapter-generator.ko.md) | [Русский](docs/skills/adapter-generator.ru.md) | [Deutsch](docs/skills/adapter-generator.de.md) | [Français](docs/skills/adapter-generator.fr.md) | [Español](docs/skills/adapter-generator.es.md) | [Português](docs/skills/adapter-generator.pt.md) | [हिन्दी](docs/skills/adapter-generator.hi.md) | [العربية](docs/skills/adapter-generator.ar.md) | [বাংলা](docs/skills/adapter-generator.bn.md) | [Bahasa Indonesia](docs/skills/adapter-generator.id.md) | [日本語](docs/skills/adapter-generator.ja.md)

स्थापित `PlatformAdapter` इंटरफ़ेस पैटर्न का पालन करते हुए नए विज्ञापन प्लेटफ़ॉर्म एडाप्टर जनरेट करें।

## पैटर्न

सभी 29 एडाप्टर `service/plugin/ads-platform/adapter/` में रहते हैं। प्रत्येक `PlatformAdapter` को 14 मेथड के साथ लागू करता है।

## टेम्पलेट

```php
<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};
use RuntimeException;
use InvalidArgumentException;
use Throwable;

class PlatformName implements PlatformAdapter
{
    // Constructor reads credentials from env()
    protected string $appId;
    protected string $secret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->appId  = env('PLATFORM_APP_ID', '');
        $this->secret = env('PLATFORM_SECRET', '');
    }

    public function code(): string { return 'platform_code'; }
    public function name(): string { return '平台名称'; }
    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // OAuth flow
    public function buildAuthUrl(string $redirectUri, string $state): string {}
    public function exchangeToken(string $code, string $redirectUri): array {}
    public function refreshToken(string $refreshToken): array {}
    public function fetchAccountInfo(string $accessToken): array {}

    // Data sync (return Generator)
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator {}
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator {}
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator {}
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator {}

    // CRUD operations
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string {}
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void {}
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void {}

    // Field mappings with value transformers for money/percentage conversion
    protected function campaignFieldMapping(): FieldMapping {}
    protected function creativeFieldMapping(): FieldMapping {}
    protected function reportFieldMapping(): FieldMapping {}

    // HTTP request with curl_errno check
    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $ch = curl_init();
        // ... always include CURLOPT_CONNECTTIMEOUT => 10
        // ... always check curl_errno($ch) after curl_exec
    }
}
```

## नियम

1. **पैसा**: सभी प्लेटफ़ॉर्म मुद्राओं को value transformer में fen (分) में बदलें
   - YUAN → ×100, माइक्रो-डॉलर → ÷10000, सेंट → कोई रूपांतरण नहीं
2. **स्थिति**: प्लेटफ़ॉर्म स्टेटस कोड को `enabled`/`paused`/`deleted` में मैप करें
3. **CTR/CVR**: प्रतिशत को दशमलव में बदलें (यदि प्लेटफ़ॉर्म % लौटाता है तो ÷100)
4. **पेजिनेशन**: Generator पैटर्न का उपयोग करें, hasMore के लिए `!empty($list)` जाँचें
5. **त्रुटि प्रबंधन**: नेटवर्क त्रुटियों के लिए `curl_errno($ch)`, HTTP स्थिति, API कोड फ़ील्ड जाँचें
6. **प्रमाणीकरण पैटर्न**: Bearer header / URL access_token / सिग्नेचर वाले कस्टम headers

## पंजीकरण

`service/plugin/ads-platform/config/bootstrap.php` में जोड़ें:
```php
use plugin\ads_platform\adapter\PlatformName;
AdapterRegistry::register(new PlatformName());
```
