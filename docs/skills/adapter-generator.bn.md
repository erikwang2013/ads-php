# Adapter Generator

[中文](docs/skills/adapter-generator.md) | [English](docs/skills/adapter-generator.en.md) | [한국어](docs/skills/adapter-generator.ko.md) | [Русский](docs/skills/adapter-generator.ru.md) | [Deutsch](docs/skills/adapter-generator.de.md) | [Français](docs/skills/adapter-generator.fr.md) | [Español](docs/skills/adapter-generator.es.md) | [Português](docs/skills/adapter-generator.pt.md) | [हिन्दी](docs/skills/adapter-generator.hi.md) | [العربية](docs/skills/adapter-generator.ar.md) | [বাংলা](docs/skills/adapter-generator.bn.md) | [Bahasa Indonesia](docs/skills/adapter-generator.id.md) | [日本語](docs/skills/adapter-generator.ja.md)

প্রতিষ্ঠিত `PlatformAdapter` ইন্টারফেস প্যাটার্ন অনুসরণ করে নতুন বিজ্ঞাপন প্ল্যাটফর্ম অ্যাডাপ্টার তৈরি করুন।

## প্যাটার্ন

সব 29টি অ্যাডাপ্টার `service/plugin/ads-platform/adapter/`-এ থাকে। প্রতিটি `PlatformAdapter` বাস্তবায়ন করে, 14টি মেথড সহ।

## টেমপ্লেট

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

## নিয়ম

1. **টাকা**: ভ্যালু ট্রান্সফরমারে সব প্ল্যাটফর্ম কারেন্সি ফেন (分)-এ রূপান্তর করুন
   - YUAN → ×100, মাইক্রো-ডলার → ÷10000, সেন্ট → কোনো রূপান্তর নেই
2. **স্ট্যাটাস**: প্ল্যাটফর্ম স্ট্যাটাস কোডকে `enabled`/`paused`/`deleted`-এ ম্যাপ করুন
3. **CTR/CVR**: পার্সেন্টেজকে দশমিকে রূপান্তর করুন (প্ল্যাটফর্ম % রিটার্ন করলে ÷100)
4. **পেজিনেশন**: Generator প্যাটার্ন ব্যবহার করুন, hasMore-এর জন্য `!empty($list)` চেক করুন
5. **এরর হ্যান্ডলিং**: নেটওয়ার্ক এররের জন্য `curl_errno($ch)` চেক করুন, HTTP স্ট্যাটাস, API কোড ফিল্ড
6. **Auth প্যাটার্ন**: Bearer header / URL access_token / সিগনেচার সহ কাস্টম headers

## রেজিস্ট্রেশন

`service/plugin/ads-platform/config/bootstrap.php`-এ যোগ করুন:
```php
use plugin\ads_platform\adapter\PlatformName;
AdapterRegistry::register(new PlatformName());
```
