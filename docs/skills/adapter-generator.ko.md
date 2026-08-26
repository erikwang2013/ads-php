# Adapter Generator

[中文](docs/skills/adapter-generator.md) | [English](docs/skills/adapter-generator.en.md) | [한국어](docs/skills/adapter-generator.ko.md) | [Русский](docs/skills/adapter-generator.ru.md) | [Deutsch](docs/skills/adapter-generator.de.md) | [Français](docs/skills/adapter-generator.fr.md) | [Español](docs/skills/adapter-generator.es.md) | [Português](docs/skills/adapter-generator.pt.md) | [हिन्दी](docs/skills/adapter-generator.hi.md) | [العربية](docs/skills/adapter-generator.ar.md) | [বাংলা](docs/skills/adapter-generator.bn.md) | [Bahasa Indonesia](docs/skills/adapter-generator.id.md) | [日本語](docs/skills/adapter-generator.ja.md)

정립된 `PlatformAdapter` 인터페이스 패턴에 따라 새 광고 플랫폼 어댑터를 생성합니다.

## 패턴

29개 어댑터 모두 `service/plugin/ads-platform/adapter/`에 있습니다. 각각 14개 메서드로 `PlatformAdapter`를 구현합니다.

## 템플릿

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

## 규칙

1. **금액**: 모든 플랫폼 통화를 value transformer에서 분(分)으로 변환
   - YUAN → ×100, 마이크로달러 → ÷10000, 센트 → 변환 없음
2. **상태**: 플랫폼 상태 코드를 `enabled`/`paused`/`deleted`로 매핑
3. **CTR/CVR**: 백분율을 소수로 변환 (플랫폼이 %를 반환하면 ÷100)
4. **페이징**: Generator 패턴 사용, hasMore는 `!empty($list)`로 확인
5. **오류 처리**: 네트워크 오류는 `curl_errno($ch)`, HTTP 상태, API code 필드 확인
6. **인증 패턴**: Bearer header / URL access_token / 서명이 포함된 커스텀 헤더

## 등록

`service/plugin/ads-platform/config/bootstrap.php`에 추가:
```php
use plugin\ads_platform\adapter\PlatformName;
AdapterRegistry::register(new PlatformName());
```
