# Adapter Generator

[中文](docs/skills/adapter-generator.md) | [English](docs/skills/adapter-generator.en.md) | [한국어](docs/skills/adapter-generator.ko.md) | [Русский](docs/skills/adapter-generator.ru.md) | [Deutsch](docs/skills/adapter-generator.de.md) | [Français](docs/skills/adapter-generator.fr.md) | [Español](docs/skills/adapter-generator.es.md) | [Português](docs/skills/adapter-generator.pt.md) | [हिन्दी](docs/skills/adapter-generator.hi.md) | [العربية](docs/skills/adapter-generator.ar.md) | [বাংলা](docs/skills/adapter-generator.bn.md) | [Bahasa Indonesia](docs/skills/adapter-generator.id.md) | [日本語](docs/skills/adapter-generator.ja.md)

Buat adaptor platform iklan baru mengikuti pola antarmuka `PlatformAdapter` yang sudah ditetapkan.

## Pola

Semua 29 adaptor berada di `service/plugin/ads-platform/adapter/`. Masing-masing mengimplementasikan `PlatformAdapter` dengan 14 metode.

## Template

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

## Aturan

1. **Uang**: Konversi semua mata uang platform ke sen (分) di value transformer
   - YUAN → ×100, micro-dollars → ÷10000, cents → tanpa konversi
2. **Status**: Petakan kode status platform ke `enabled`/`paused`/`deleted`
3. **CTR/CVR**: Konversi persentase ke desimal (÷100 jika platform mengembalikan %)
4. **Paginasi**: Gunakan pola Generator, periksa `!empty($list)` untuk hasMore
5. **Penanganan error**: Periksa `curl_errno($ch)` untuk error jaringan, status HTTP, field kode API
6. **Pola autentikasi**: Header Bearer / access_token URL / header kustom dengan tanda tangan

## Registrasi

Tambahkan ke `service/plugin/ads-platform/config/bootstrap.php`:
```php
use plugin\ads_platform\adapter\PlatformName;
AdapterRegistry::register(new PlatformName());
```
