# Adapter-Generator

[中文](docs/skills/adapter-generator.md) | [English](docs/skills/adapter-generator.en.md) | [한국어](docs/skills/adapter-generator.ko.md) | [Русский](docs/skills/adapter-generator.ru.md) | [Deutsch](docs/skills/adapter-generator.de.md) | [Français](docs/skills/adapter-generator.fr.md) | [Español](docs/skills/adapter-generator.es.md) | [Português](docs/skills/adapter-generator.pt.md) | [हिन्दी](docs/skills/adapter-generator.hi.md) | [العربية](docs/skills/adapter-generator.ar.md) | [বাংলা](docs/skills/adapter-generator.bn.md) | [Bahasa Indonesia](docs/skills/adapter-generator.id.md) | [日本語](docs/skills/adapter-generator.ja.md)

Neue Werbeplattform-Adapter gemäß dem etablierten `PlatformAdapter`-Interface-Muster generieren.

## Muster

Alle 29 Adapter liegen in `service/plugin/ads-platform/adapter/`. Jeder implementiert `PlatformAdapter` mit 14 Methoden.

## Vorlage

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

## Regeln

1. **Geld**: Alle Plattformwährungen im Value-Transformer in Fen (分) umrechnen
   - YUAN → ×100, Mikro-Dollar → ÷10000, Cents → keine Umrechnung
2. **Status**: Plattform-Statuscodes auf `enabled`/`paused`/`deleted` abbilden
3. **CTR/CVR**: Prozentsätze in Dezimalzahlen umrechnen (÷100, wenn die Plattform % liefert)
4. **Paginierung**: Generator-Muster verwenden, `!empty($list)` für hasMore prüfen
5. **Fehlerbehandlung**: `curl_errno($ch)` für Netzwerkfehler prüfen, HTTP-Status, API-Code-Feld
6. **Auth-Muster**: Bearer-Header / URL access_token / benutzerdefinierte Header mit Signatur

## Registrierung

Zu `service/plugin/ads-platform/config/bootstrap.php` hinzufügen:
```php
use plugin\ads_platform\adapter\PlatformName;
AdapterRegistry::register(new PlatformName());
```
