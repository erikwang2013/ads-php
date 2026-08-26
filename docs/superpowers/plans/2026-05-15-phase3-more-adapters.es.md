# Fase 3: Plan de Implementación — Ampliación de Adaptadores de Plataformas Publicitarias

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **Para agentes trabajadores:** SUB-HABILIDAD REQUERIDA: usar superpowers:subagent-driven-development para implementar.

**Objetivo:** Añadir adaptadores para cuatro plataformas: Tencent Ads, Umeng, Kuaishou Mágico (Magnet Engine) y Xiaohongshu Dandelion.

**Adaptadores existentes (Fase 1+2):** Ocean Engine, Baidu Marketing, Taobao/Alimama

**Arquitectura:** Cada adaptador implementa la interfaz `PlatformAdapter`, se registra en `AdapterRegistry` y queda disponible para el flujo de autorización OAuth, las tareas de sincronización de datos y el panel de administración frontend.

---

## Tarea 13: Crear el adaptador de Tencent Ads

**Archivos:**
- Crear: `service/plugin/ads-platform/adapter/Tencent.php`
- Modificar: `service/plugin/ads-platform/config/bootstrap.php`

### Especificación del adaptador

API de Tencent Ads (Guangdiantong):
- URL OAuth: `https://developers.e.qq.com/oauth/authorize`
- URL de token: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- Autenticación: parámetro URL `access_token` + `nonce`/`timestamp` anti-repetición
- Campañas: `campaigns/get` + `campaigns/add` + `campaigns/update`
- Informes: `daily_reports/get` (asíncrono: crear tarea → sondear → obtener)
- Unidad monetaria: céntimos (coincide con el modelo unificado, sin conversión)
- Mapeo de estados: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Firma API específica de Tencent

Tencent usa `access_token` como parámetro URL, no requiere firma MD5, pero sí `nonce` (número aleatorio) + `timestamp` anti-repetición.

```php
protected function request(string $method, string $path, array $params, string $accessToken): array
{
    $url = $this->baseUrl . ltrim($path, '/');
    $params['access_token'] = $accessToken;
    $params['nonce'] = bin2hex(random_bytes(8));
    $params['timestamp'] = time();

    $ch = curl_init();
    if ($method === 'GET') {
        $url .= '?' . http_build_query($params);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new \RuntimeException('Tencent API network error: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($body, true);
    if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
        throw new \RuntimeException(
            'Tencent API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
        );
    }
    return $decoded;
}
```

**Puntos clave del mapeo de campos:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (la unidad ya es céntimo, sin conversión)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- En informes `cost` (céntimos)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Tarea 14: Crear el adaptador de Umeng

**Archivos:**
- Crear: `service/plugin/ads-platform/adapter/Umeng.php`
- Modificar: `service/plugin/ads-platform/config/bootstrap.php`

### Especificación del adaptador

Umeng (U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- Autenticación: API Key + API Secret + firma MD5
- Umeng se centra en la **monitorización de efectividad promocional**, diferente de las plataformas de publicidad — no crea/gestiona campañas directamente, sino que rastrea los datos promocionales de cada canal
- capabilities: `['report', 'oauth']` (no admite campaign/create/update/toggle)
- Endpoint de informes: `/v1/ad_analytics/report` devuelve datos promocionales por canal/fecha
- fetchCampaigns devuelve vacío (Umeng no crea campañas propias)
- fetchReports obtiene los datos de efectividad promocional y los mapea al modelo de informe unificado

### Algoritmo de firma de Umeng

```
sign = md5(method + url + body + api_secret)
```

La información de autenticación se envía por cabecera HTTP: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Puntos clave del mapeo de campos:**
- `channel` → `platform_campaign_id` (el identificador de canal se mapea a la dimensión de campaña)
- `pv` → `impressions` (impresiones)
- `click` → `clicks` (clics)
- `activation` → `conversions` (activaciones/conversiones)
- Unidad de `cost`: yuanes → céntimos (×100)

---

## Tarea 15: Crear el adaptador de Kuaishou Magnet Engine

**Archivos:**
- Crear: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modificar: `service/plugin/ads-platform/config/bootstrap.php`

### Especificación del adaptador

Kuaishou Magnet Engine (Kwai Ads):
- URL OAuth: `https://developers.e.kuaishou.com/oauth/authorize`
- URL de token: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- Autenticación: Header `access_token`
- Campañas: `/campaign/list` + `/campaign/create` + `/campaign/update`
- Informes: `/report/campaign/report` (síncrono)
- Unidad monetaria: yuanes → céntimos (×100)

**Puntos clave del mapeo de campos:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (yuanes→céntimos ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- En informes `charge`→`cost` (yuanes→céntimos)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Tarea 16: Crear el adaptador de Xiaohongshu Dandelion

**Archivos:**
- Crear: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modificar: `service/plugin/ads-platform/config/bootstrap.php`

### Especificación del adaptador

Xiaohongshu Dandelion (plataforma Xiaohongshu Spotlight):
- URL OAuth: `https://ark.xiaohongshu.com/oauth/authorize`
- URL de token: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- Autenticación: Header `access_token` (`Authorization: Bearer xxx`)
- Campañas: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Informes: `/v1/report/campaign/report`
- Unidad monetaria: céntimos (la API de Xiaohongshu devuelve céntimos, sin conversión)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Puntos clave del mapeo de campos:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (unidad: céntimos)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- En informes `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Criterios de aceptación

1. ✅ El adaptador de Tencent Ads implementa los 13 métodos de PlatformAdapter
2. ✅ El adaptador de Umeng implementa las capacidades report + oauth (Umeng no admite operaciones de campaña)
3. ✅ El adaptador de Kuaishou Magnet Engine implementa los 13 métodos
4. ✅ El adaptador de Xiaohongshu Dandelion implementa los 13 métodos
5. ✅ Los 4 adaptadores están registrados en bootstrap.php
6. ✅ `GET /api/v1/platforms` devuelve 7 plataformas (incluidas las 3 anteriores)
7. ✅ Todos los adaptadores manejan correctamente los errores de curl (curl_errno + CURLOPT_CONNECTTIMEOUT)
