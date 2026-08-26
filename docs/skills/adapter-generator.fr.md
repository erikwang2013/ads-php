# Adapter Generator

[中文](docs/skills/adapter-generator.md) | [English](docs/skills/adapter-generator.en.md) | [한국어](docs/skills/adapter-generator.ko.md) | [Русский](docs/skills/adapter-generator.ru.md) | [Deutsch](docs/skills/adapter-generator.de.md) | [Français](docs/skills/adapter-generator.fr.md) | [Español](docs/skills/adapter-generator.es.md) | [Português](docs/skills/adapter-generator.pt.md) | [हिन्दी](docs/skills/adapter-generator.hi.md) | [العربية](docs/skills/adapter-generator.ar.md) | [বাংলা](docs/skills/adapter-generator.bn.md) | [Bahasa Indonesia](docs/skills/adapter-generator.id.md) | [日本語](docs/skills/adapter-generator.ja.md)

Générer de nouveaux adaptateurs de plateformes publicitaires en suivant le modèle d'interface `PlatformAdapter` établi.

## Modèle

Les 29 adaptateurs vivent dans `service/plugin/ads-platform/adapter/`. Chacun implémente `PlatformAdapter` avec 14 méthodes.

## Modèle de code

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

## Règles

1. **Argent** : Convertir toutes les devises des plateformes en fen (分) dans le transformateur de valeurs
   - YUAN → ×100, micro-dollars → ÷10000, centimes → aucune conversion
2. **Statut** : Mapper les codes de statut des plateformes vers `enabled`/`paused`/`deleted`
3. **CTR/CVR** : Convertir les pourcentages en décimales (÷100 si la plateforme renvoie des %)
4. **Pagination** : Utiliser le modèle Generator, vérifier `!empty($list)` pour hasMore
5. **Gestion d'erreurs** : Vérifier `curl_errno($ch)` pour les erreurs réseau, le statut HTTP, le champ code API
6. **Modèles d'authentification** : en-tête Bearer / access_token dans l'URL / en-têtes personnalisés avec signature

## Enregistrement

Ajouter dans `service/plugin/ads-platform/config/bootstrap.php` :
```php
use plugin\ads_platform\adapter\PlatformName;
AdapterRegistry::register(new PlatformName());
```
