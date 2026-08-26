# Fase 2: Ampliación de Plataformas Publicitarias + Motor de Informes — Plan de Implementación

[中文](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.md) | [English](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase2-adapters-reports.ja.md)

> **Para trabajadores agénticos:** SUB-HABILIDAD OBLIGATORIA: Usa superpowers:subagent-driven-development para implementar este plan tarea por tarea.

**Objetivo:** Añadir adaptadores de Baidu Marketing y Taobao Ads, implementar la programación real de sincronización de datos y mejorar el motor de informes con soporte de consultas personalizadas multidimensionales.

**Arquitectura:** Cada plataforma publicitaria implementa la interfaz PlatformAdapter, registrada de forma unificada a través de AdapterRegistry. La sincronización de datos usa la programación cron de webman/crontab; durante la sincronización, los datos nativos de la plataforma se convierten al formato unificado mediante FieldMapping y se escriben en la tabla report_metrics. El motor de informes amplía la capacidad de consulta personalizada de CampaignController sobre la base del DashboardController existente.

**Tech Stack:** PHP 8.2+, webman v2, webman/crontab, curl (llamadas API), Vue 3, ECharts 5

**Base existente de la Fase 1:**
- La interfaz PlatformAdapter, AdapterRegistry y FieldMapping están listos
- El adaptador de Juliang (巨量引擎) ya implementa la cadena completa (OAuth → sincronización → operaciones de campaña)
- La tabla report_metrics ya está creada; DashboardController::summary() ya tiene capacidad de agregación por fecha/plataforma
- La tabla campaigns ya está creada; CampaignController ya tiene CRUD completo
- El flujo de autorización general de OAuthService está listo

---

## Estructura de archivos (nuevos/modificados en la Fase 2)

```
service/
├── plugin/
│   ├── ads-platform/
│   │   └── adapter/
│   │       ├── Baidu.php              # Nuevo: adaptador de Baidu Marketing
│   │       ├── Taobao.php             # Nuevo: adaptador de Taobao Ads
│   │       └── Juliang.php            # Modificado: añade comentarios con ejemplos de respuestas API
│   ├── ads-task/
│   │   ├── config/plugin.php          # Nuevo
│   │   ├── task/
│   │   │   ├── DataSyncTask.php       # Nuevo: tarea de sincronización de datos genérica
│   │   │   └── TokenRefreshTask.php   # Nuevo: tarea de renovación automática de Token
│   │   └── config/cron.php            # Nuevo: configuración de tareas programadas
│   ├── ads-api/
│   │   ├── controller/
│   │   │   ├── ReportController.php   # Nuevo: interfaz de informes personalizados
│   │   │   └── CampaignController.php # Modificado: añade interfaz de operaciones por lotes
│   │   └── config/route.php           # Modificado: añade rutas de informes
│   └── ads-report/
│       ├── config/plugin.php          # Nuevo
│       └── service/
│           └── ReportBuilder.php      # Nuevo: servicio de precomputación de informes
```

---

### Tarea 10: Crear el adaptador de Baidu Marketing

**Files:**
- Crear: `service/plugin/ads-platform/adapter/Baidu.php`
- Modificar: `service/plugin/ads-platform/config/bootstrap.php`

Integración principal de la API de Baidu Marketing:
- Dirección de autorización OAuth: `https://u.baidu.com/oauth/authorize`
- Intercambio de token: `https://u.baidu.com/oauth/token`
- Consulta de campañas: `/json/sms/service/CampaignService/getCampaign`
- Consulta de informes: `/json/sms/service/ReportService/getProfessionalReportId` + `getReportData`

#### Paso 1: Crear el adaptador Baidu.php

```php
<?php
namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};

class Baidu implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://api.baidu.com/';
    protected string $authUrl  = 'https://u.baidu.com/oauth/authorize';
    protected string $tokenUrl = 'https://u.baidu.com/oauth/token';

    public function __construct()
    {
        $this->appId  = getenv('BAIDU_APP_ID') ?: '';
        $this->secret = getenv('BAIDU_SECRET') ?: '';
    }

    public function code(): string { return 'baidu'; }
    public function name(): string { return '百度营销'; }
    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'       => $this->appId,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
            'scope'        => 'basic',
        ]);
        return $this->authUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request($this->tokenUrl, 'POST', [
            'app_id'       => $this->appId,
            'secret'       => $this->secret,
            'auth_code'    => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
        return [
            'access_token'   => $resp['data']['accessToken'] ?? '',
            'refresh_token'  => $resp['data']['refreshToken'] ?? '',
            'expires_in'     => $resp['data']['expiresIn'] ?? 86400,
            'advertiser_ids' => $resp['data']['userIds'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request($this->tokenUrl, 'POST', [
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'  => $data['accessToken'] ?? '',
            'refresh_token' => $data['refreshToken'] ?? '',
            'expires_in'    => $data['expiresIn'] ?? 86400,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->signedRequest('/json/sms/service/UserService/getUserInfo', [], $accessToken);
        $list = $resp['data'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['userId'] ?? ''),
            'account_name'           => $item['userName'] ?? '',
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $resp = $this->signedRequest('/json/sms/service/CampaignService/getCampaign', [
            'campaignFields' => ['campaignId', 'campaignName', 'budget', 'status', 'startDate', 'endDate'],
        ], $accessToken);
        foreach (($resp['data'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $resp = $this->signedRequest('/json/sms/service/AdgroupService/getAdgroup', [
            'adgroupFields' => ['adgroupId', 'adgroupName', 'campaignId', 'status', 'bid', 'bidType'],
        ], $accessToken);
        $filtered = array_values(array_filter($resp['data'] ?? [], fn($r) => ($r['campaignId'] ?? '') === $campaignId));
        foreach ($filtered as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $resp = $this->signedRequest('/json/sms/service/CreativeService/getCreative', [
            'creativeFields' => ['creativeId', 'title', 'status'],
        ], $accessToken);
        foreach (($resp['data'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        // 百度报表两步：先创建报表任务，再获取数据
        $reportReq = [
            'reportType' => $req->granularity === 'hourly' ? 1 : 2,
            'performanceData' => ['impressions', 'clicks', 'cost', 'ctr', 'cpc', 'cpm', 'conversions'],
            'startDate'  => $req->dateStart,
            'endDate'    => $req->dateEnd,
        ];

        $createResp = $this->signedRequest('/json/sms/service/ReportService/getProfessionalReportId', $reportReq, $accessToken);
        $reportId = $createResp['data']['reportId'] ?? '';

        if (!$reportId) {
            return;
        }

        // 轮询等待报表生成
        $maxRetries = 10;
        for ($i = 0; $i < $maxRetries; $i++) {
            sleep(2);
            $statusResp = $this->signedRequest('/json/sms/service/ReportService/getReportState', [
                'reportId' => $reportId,
            ], $accessToken);
            if (($statusResp['data']['isGenerated'] ?? 0) === 3) {
                break;
            }
        }

        $dataResp = $this->signedRequest('/json/sms/service/ReportService/getReportData', [
            'reportId' => $reportId,
        ], $accessToken);

        foreach (($dataResp['data'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->signedRequest('/json/sms/service/CampaignService/addCampaign', [
            'campaignTypes' => [[
                'campaignName' => $data->name,
                'budget'       => $data->dailyBudget / 100,  // 分 → 元
            ]],
        ], $accessToken);
        return (string) ($resp['data'][0]['campaignId'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $this->signedRequest('/json/sms/service/CampaignService/updateCampaign', [
            'campaignTypes' => [[
                'campaignId'   => $platformId,
                'campaignName' => $data->name,
                'budget'       => $data->dailyBudget > 0 ? $data->dailyBudget / 100 : null,
            ]],
        ], $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->signedRequest('/json/sms/service/CampaignService/updateCampaign', [
            'campaignTypes' => [[
                'campaignId' => $platformId,
                'status'     => $enabled ? 1 : 2,
            ]],
        ], $accessToken);
    }

    // —— 字段映射 ——

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId'   => 'platform_campaign_id',
            'campaignName' => 'name',
            'budget'       => 'daily_budget',
            'status'       => 'status',
        ], [
            1 => 'enabled', 2 => 'paused', 3 => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adgroupId'   => 'platform_adgroup_id',
            'adgroupName' => 'name',
            'bid'         => 'bid_amount',
            'status'      => 'status',
        ], [1 => 'enabled', 2 => 'paused', 3 => 'deleted']);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creativeId' => 'platform_creative_id',
            'title'      => 'title',
            'status'     => 'status',
        ], [1 => 'enabled', 2 => 'paused']);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'cost'        => 'cost',
            'ctr'         => 'ctr',
            'cpc'         => 'cpc',
            'cpm'         => 'cpm',
            'conversions' => 'conversions',
        ], [], function (array $unified): array {
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) ($unified['cost'] * 100); // 元 → 分
            }
            return $unified;
        });
    }

    // —— 私有方法 ——

    protected function signedRequest(string $path, array $params, string $accessToken): array
    {
        $url = $this->baseUrl . ltrim($path, '/');
        $payload = [
            'header' => [
                'username'   => $this->appId,
                'password'   => $this->secret,
                'token'      => $accessToken,
                'target'     => 'baidu',
                'accessMode' => 1,
            ],
            'body' => $params,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Baidu API network error: ' . $err);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        $errno = $decoded['header']['failures'][0]['code'] ?? 0;
        if ($httpCode !== 200 || $errno !== 0) {
            $msg = $decoded['header']['failures'][0]['message'] ?? ('HTTP ' . $httpCode);
            throw new \RuntimeException('Baidu API error: ' . $msg);
        }
        return $decoded;
    }

    protected function request(string $url, string $method, array $params = []): array
    {
        $ch = curl_init();
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Baidu API network error: ' . $err);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new \RuntimeException('Baidu OAuth error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode));
        }
        return $decoded;
    }
}
```

#### Paso 2: Registrar el adaptador de Baidu

```php
// 在 service/plugin/ads-platform/config/bootstrap.php 中添加
AdapterRegistry::register(new \plugin\ads_platform\adapter\Baidu());
```

#### Paso 3: Confirmar (commit)

```bash
git add service/plugin/ads-platform/adapter/Baidu.php service/plugin/ads-platform/config/bootstrap.php
git commit -m "feat: add Baidu marketing adapter"
```

---

### Tarea 11: Crear el adaptador de Taobao Ads

**Files:**
- Crear: `service/plugin/ads-platform/adapter/Taobao.php`
- Modificar: `service/plugin/ads-platform/config/bootstrap.php`

Integración principal de la API de Taobao Ads (Alimama):
- Autorización OAuth: OAuth 2.0 de la plataforma abierta de Taobao
- Endpoint de API: `https://api.taobao.com/router/rest`
- Campañas: `alimama.campaign.get`
- Informes: `alimama.report.get`

#### Paso 1: Crear Taobao.php

```php
<?php
namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};

class Taobao implements PlatformAdapter
{
    protected string $appKey;
    protected string $secret;
    protected string $baseUrl = 'https://api.taobao.com/router/rest';
    protected string $authUrl = 'https://oauth.taobao.com/authorize';

    public function __construct()
    {
        $this->appKey = getenv('TAOBAO_APP_KEY') ?: '';
        $this->secret = getenv('TAOBAO_SECRET') ?: '';
    }

    public function code(): string { return 'taobao'; }
    public function name(): string { return '淘宝广告'; }
    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->appKey,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);
        return $this->authUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('taobao.oauth.token', [
            'client_id'     => $this->appKey,
            'client_secret' => $this->secret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => $data['expires_in'] ?? 86400,
            'advertiser_ids' => [$data['taobao_user_id'] ?? '0'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('taobao.oauth.token', [
            'client_id'     => $this->appKey,
            'client_secret' => $this->secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => $data['expires_in'] ?? 86400,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('taobao.user.get', [
            'fields' => 'user_id,nick',
        ], $accessToken);
        $data = $resp['data'] ?? [];
        $user = $data['user'] ?? [];
        return [[
            'account_id_on_platform' => (string) ($user['user_id'] ?? ''),
            'account_name'           => $user['nick'] ?? '',
        ]];
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('alimama.campaign.get', [
                'page_no'   => $page,
                'page_size' => 100,
            ], $accessToken);
            $list = $resp['data']['campaign_list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $resp = $this->request('alimama.adgroup.get', [
            'campaign_id' => (int) $campaignId,
        ], $accessToken);
        foreach (($resp['data']['adgroup_list'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $resp = $this->request('alimama.creative.get', [
            'adgroup_id' => (int) $adGroupId,
        ], $accessToken);
        foreach (($resp['data']['creative_list'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('alimama.report.get', [
                'start_date'    => $req->dateStart,
                'end_date'      => $req->dateEnd,
                'granularity'   => $req->granularity === 'hourly' ? 'hourly' : 'daily',
                'page_no'       => $page,
                'page_size'     => min($req->pageSize, 200),
            ], $accessToken);
            $list = $resp['data']['report_list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('alimama.campaign.create', [
            'campaign_name' => $data->name,
            'day_budget'    => $data->dailyBudget / 100,
        ], $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'campaign_id'   => $platformId,
            'campaign_name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['day_budget'] = $data->dailyBudget / 100;
        }
        $this->request('alimama.campaign.update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('alimama.campaign.status.update', [
            'campaign_id'    => $platformId,
            'online_status'  => $enabled ? 1 : 0,
        ], $accessToken);
    }

    // —— 字段映射 ——

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'day_budget'    => 'daily_budget',
            'online_status' => 'status',
        ], [
            1 => 'enabled', 0 => 'paused',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adgroup_id'   => 'platform_adgroup_id',
            'adgroup_name' => 'name',
            'bid_price'    => 'bid_amount',
            'online_status'=> 'status',
        ], [1 => 'enabled', 0 => 'paused']);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creative_id'   => 'platform_creative_id',
            'creative_title'=> 'title',
            'online_status' => 'status',
        ], [1 => 'enabled', 0 => 'paused']);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'cost'        => 'cost',
            'ctr'         => 'ctr',
            'cpc'         => 'cpc',
            'cpm'         => 'cpm',
            'conversions' => 'conversions',
        ], [], function (array $unified): array {
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) ($unified['cost'] * 100);
            }
            return $unified;
        });
    }

    // —— 淘宝 API 签名请求 ——

    protected function request(string $method, array $params, ?string $accessToken = null): array
    {
        $params['method']       = $method;
        $params['app_key']      = $this->appKey;
        $params['timestamp']    = date('Y-m-d H:i:s');
        $params['v']            = '2.0';
        $params['format']       = 'json';
        $params['sign_method']  = 'md5';
        if ($accessToken) {
            $params['session'] = $accessToken;
        }
        $params['sign'] = $this->sign($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Taobao API network error: ' . $err);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        $errorResp = $decoded['error_response'] ?? null;
        if ($httpCode !== 200 || $errorResp) {
            $msg = $errorResp['sub_msg'] ?? $errorResp['msg'] ?? ('HTTP ' . $httpCode);
            throw new \RuntimeException('Taobao API error: ' . $msg);
        }
        $responseKey = str_replace('.', '_', $method) . '_response';
        return $decoded[$responseKey] ?? $decoded;
    }

    protected function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($k !== 'sign' && $v !== '' && $v !== null) {
                $str .= $k . $v;
            }
        }
        return strtoupper(md5($this->secret . $str . $this->secret));
    }
}
```

#### Paso 2: Registrar el adaptador de Taobao

```php
// 在 bootstrap.php 中添加
AdapterRegistry::register(new \plugin\ads_platform\adapter\Taobao());
```

#### Paso 3: Confirmar (commit)

```bash
git add service/plugin/ads-platform/adapter/Taobao.php service/plugin/ads-platform/config/bootstrap.php
git commit -m "feat: add Taobao (Alimama) advertising adapter"
```

---

### Tarea 12: Crear la tarea de sincronización de datos + mejora del motor de informes

**Files:**
- Crear: `service/plugin/ads-task/config/plugin.php`
- Crear: `service/plugin/ads-task/task/DataSyncTask.php`
- Crear: `service/plugin/ads-task/task/TokenRefreshTask.php`
- Crear: `service/plugin/ads-task/config/cron.php`
- Crear: `service/plugin/ads-report/config/plugin.php`
- Crear: `service/plugin/ads-report/service/ReportBuilder.php`
- Crear: `service/plugin/ads-api/controller/ReportController.php`
- Modificar: `service/plugin/ads-api/config/route.php`

#### Paso 1: Crear el plugin ads-task

`service/plugin/ads-task/config/plugin.php`:
```php
<?php
return ['enable' => true, 'name' => 'ads-task', 'version' => '1.0.0'];
```

#### Paso 2: Crear TokenRefreshTask

```php
<?php
namespace plugin\ads_task\task;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;

class TokenRefreshTask
{
    public function execute(): void
    {
        $accounts = PlatformAccount::query()
            ->where('status', 1)
            ->whereNotNull('refresh_token')
            ->where('refresh_token', '!=', '')
            ->get();

        $refreshed = 0;
        foreach ($accounts as $account) {
            if (!$account->isTokenExpired()) continue;

            try {
                $adapter = AdapterRegistry::get($account->platform);
                if (!$adapter) continue;

                $tokenData = $adapter->refreshToken($account->refresh_token);
                $account->update([
                    'access_token'     => $tokenData['access_token'],
                    'refresh_token'    => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
                ]);
                $refreshed++;
            } catch (\Throwable $e) {
                echo "Token refresh failed for account {$account->id}: {$e->getMessage()}\n";
            }
        }

        echo "Refreshed {$refreshed} tokens.\n";
    }
}
```

#### Paso 3: Crear DataSyncTask

```php
<?php
namespace plugin\ads_task\task;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\ReportRequest;
use Illuminate\Database\Capsule\Manager as DB;

class DataSyncTask
{
    public function execute(): void
    {
        $accounts = PlatformAccount::query()
            ->where('status', 1)
            ->where('sync_enabled', 1)
            ->get();

        foreach ($accounts as $account) {
            echo "Syncing account {$account->id} ({$account->platform})...\n";

            try {
                $adapter = AdapterRegistry::get($account->platform);
                if (!$adapter) continue;

                // Sync campaigns
                foreach ($adapter->fetchCampaigns($account->access_token, $account->account_id_on_platform) as $row) {
                    DB::table('campaigns')->updateOrInsert(
                        [
                            'platform_account_id'  => $account->id,
                            'platform_campaign_id' => $row['platform_campaign_id'],
                        ],
                        [
                            'tenant_id'   => $account->tenant_id,
                            'platform'    => $account->platform,
                            'name'        => $row['name'] ?? '',
                            'daily_budget'=> $row['daily_budget'] ?? 0,
                            'status'      => $row['status'] ?? null,
                            'extra'       => json_encode($row['extra'] ?? [], JSON_UNESCAPED_UNICODE),
                            'synced_at'   => now(),
                            'updated_at'  => now(),
                        ]
                    );
                }

                // Sync reports (last 2 days)
                $req = new ReportRequest(
                    dateStart: date('Y-m-d', strtotime('-2 days')),
                    dateEnd:   date('Y-m-d'),
                    granularity: 'daily',
                    metrics: ['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'cpc', 'cpm', 'roi'],
                );

                foreach ($adapter->fetchReports($account->access_token, $account->account_id_on_platform, $req) as $row) {
                    $campaignId = null;
                    if (!empty($row['platform_campaign_id'])) {
                        $campaign = DB::table('campaigns')
                            ->where('platform_campaign_id', $row['platform_campaign_id'])
                            ->where('platform_account_id', $account->id)
                            ->first();
                        $campaignId = $campaign->id ?? null;
                    }

                    DB::table('report_metrics')->updateOrInsert(
                        [
                            'tenant_id'           => $account->tenant_id,
                            'platform'            => $account->platform,
                            'platform_account_id' => $account->id,
                            'campaign_id'         => $campaignId,
                            'date'                => $row['date'] ?? date('Y-m-d'),
                            'granularity'         => 'daily',
                        ],
                        [
                            'cost'         => $row['cost'] ?? 0,
                            'impressions'  => $row['impressions'] ?? 0,
                            'clicks'       => $row['clicks'] ?? 0,
                            'conversions'  => $row['conversions'] ?? 0,
                            'ctr'          => $row['ctr'] ?? 0,
                            'cpm'          => $row['cpm'] ?? 0,
                            'cpc'          => $row['cpc'] ?? 0,
                            'cvr'          => $row['cvr'] ?? 0,
                        ]
                    );
                }

                $account->update(['last_sync_at' => now()]);
                echo "  Done.\n";

            } catch (\Throwable $e) {
                echo "  Failed: {$e->getMessage()}\n";
            }
        }
    }
}
```

#### Paso 4: Crear la configuración de tareas programadas cron.php

```php
<?php
return [
    // Token 每 55 分钟刷新一次（避免整点高峰）
    [
        'name'     => 'TokenRefresh',
        'handler'  => [plugin\ads_task\task\TokenRefreshTask::class, 'execute'],
        'rule'     => '55 */1 * * *',
    ],
    // 数据同步每 10 分钟执行
    [
        'name'     => 'DataSync',
        'handler'  => [plugin\ads_task\task\DataSyncTask::class, 'execute'],
        'rule'     => '*/10 * * * *',
    ],
];
```

#### Paso 5: Crear el plugin ads-report + ReportBuilder

`service/plugin/ads-report/config/plugin.php`:
```php
<?php
return ['enable' => true, 'name' => 'ads-report', 'version' => '1.0.0'];
```

`service/plugin/ads-report/service/ReportBuilder.php`:
```php
<?php
namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class ReportBuilder
{
    public function buildCustom(int $tenantId, array $params): array
    {
        $dateStart = $params['date_start'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateEnd   = $params['date_end']   ?? date('Y-m-d');
        $dimensions = $params['dimensions']  ?? ['platform'];
        $metrics    = $params['metrics']     ?? ['cost', 'impressions', 'clicks'];

        $metricColumns = $this->metricColumns($metrics);
        $groupCols     = $this->dimensionColumns($dimensions);

        $query = DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd]);

        foreach ($groupCols as $col) {
            $query->groupBy($col)->select($col);
        }
        foreach ($metricColumns as $alias => $raw) {
            $query->selectRaw("{$raw} as {$alias}");
        }
        if (in_array('date', $dimensions)) {
            $query->orderBy('date');
        }
        $query->orderByDesc(array_values($metricColumns)[0] ?? 'cost');

        $perPage = min((int) ($params['per_page'] ?? 20), 100);
        $paginator = $query->paginate($perPage);

        return [
            'list'       => $paginator->items(),
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'per_page'    => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => (int) ceil($paginator->total() / $paginator->perPage()),
            ],
        ];
    }

    protected function metricColumns(array $metrics): array
    {
        $map = [
            'cost'         => 'COALESCE(SUM(cost), 0)',
            'impressions'  => 'COALESCE(SUM(impressions), 0)',
            'clicks'       => 'COALESCE(SUM(clicks), 0)',
            'conversions'  => 'COALESCE(SUM(conversions), 0)',
            'ctr'          => 'CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(clicks)/SUM(impressions), 6) ELSE 0 END',
            'cvr'          => 'CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(conversions)/SUM(clicks), 6) ELSE 0 END',
            'cpc'          => 'CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(cost)/SUM(clicks), 2) ELSE 0 END',
            'cpm'          => 'CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(cost)/SUM(impressions)*1000, 2) ELSE 0 END',
            'roi'          => 'CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(conversions)/SUM(cost)*100, 2) ELSE 0 END',
        ];
        $result = [];
        foreach ($metrics as $m) {
            if (isset($map[$m])) $result[$m] = $map[$m];
        }
        return $result;
    }

    protected function dimensionColumns(array $dimensions): array
    {
        return array_intersect($dimensions, ['platform', 'date', 'campaign_id', 'granularity']);
    }
}
```

#### Paso 6: Crear ReportController

```php
<?php
namespace plugin\ads_api\controller;

use plugin\ads_report\service\ReportBuilder;
use Webman\Http\Request;
use app\support\ApiResponse;

class ReportController
{
    public function custom(Request $request): \Webman\Http\Response
    {
        $builder = new ReportBuilder();
        $result = $builder->buildCustom(
            $request->tenantId ?? 1,
            $request->all()
        );
        return ApiResponse::success($result);
    }
}
```

#### Paso 7: Añadir la ruta de informes

Añadir en el grupo autenticado de `route.php`:
```php
\Webman\Route::get('/reports/custom', [ReportController::class, 'custom']);
```

#### Paso 8: Confirmar (commit)

```bash
git add service/plugin/ads-task/ service/plugin/ads-report/ service/plugin/ads-api/
git commit -m "feat: add data sync tasks, report builder, and custom report API"
```

---

## Criterios de aceptación

1. ✅ El adaptador de Baidu Marketing implementa todos los métodos de PlatformAdapter
2. ✅ El adaptador de Taobao Ads implementa todos los métodos de PlatformAdapter (incluido el algoritmo de firma de Taobao)
3. ✅ El procesamiento del callback OAuth se distribuye de forma unificada a través de AdapterRegistry
4. ✅ Los adaptadores de Baidu/Taobao se registran en bootstrap.php
5. ✅ TokenRefreshTask escanea y renueva los tokens caducados cada 55 minutos
6. ✅ DataSyncTask obtiene los datos de campaña e informes de cada plataforma cada 10 minutos
7. ✅ La API de consulta de informes personalizados soporta combinaciones de múltiples dimensiones (platform/date/campaign) y múltiples métricas
8. ✅ Los datos sincronizados se escriben correctamente en la tabla report_metrics
