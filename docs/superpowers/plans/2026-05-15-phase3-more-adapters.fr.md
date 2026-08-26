# Phase 3 : Extension des adaptateurs de plateformes publicitaires — Plan d'implémentation

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **Pour les agents autonomes :** SOUS-COMPÉTENCE REQUISE : utiliser superpowers:subagent-driven-development pour l'implémentation.

**Objectif :** Ajouter les adaptateurs pour quatre plateformes : 腾讯广告 (Tencent Ads), 友盟 (Umeng), 快手磁力引擎 (Moteur magnétique Kuaishou), 小红书蒲公英 (Xiaohongshu Dandelion).

**Adaptateurs existants (Phase 1+2) :** 巨量引擎 (Ocean Engine), 百度营销 (Baidu Marketing), 淘宝/阿里妈妈 (Taobao/Alimama)

**Architecture :** Chaque adaptateur implémente l'interface `PlatformAdapter`, s'enregistre dans `AdapterRegistry`, puis peut être appelé de manière uniforme par le flux d'autorisation OAuth, les tâches de synchronisation de données et le panneau d'administration front-end.

---

## Task 13 : Créer l'adaptateur 腾讯广告

**Fichiers :**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spécifications de l'adaptateur

API 腾讯广告 (广点通) :
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- Méthode d'authentification : paramètre URL `access_token` + `nonce`/`timestamp` anti-rejeu
- Campagnes : `campaigns/get` + `campaigns/add` + `campaigns/update`
- Rapports : `daily_reports/get` (asynchrone : créer la tâche → interroger → récupérer)
- Unité monétaire : fen (分) (cohérent avec le modèle unifié, aucune conversion nécessaire)
- Mapping des statuts : `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Signature API spécifique à Tencent

Tencent utilise `access_token` comme paramètre URL, pas de signature MD5, mais nécessite `nonce` (nombre aléatoire) + `timestamp` anti-rejeu.

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

**Points clés du mapping des champs :**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget`（unité déjà en fen, aucune conversion）
- `configured_status` → `status`（AD_STATUS_NORMAL/SUSPEND/DELETE）
- Dans les rapports : `cost`（fen）/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14 : Créer l'adaptateur 友盟

**Fichiers :**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spécifications de l'adaptateur

友盟 (Umeng U-App + U-Ads) :
- API Base: `https://api.open.umeng.com/`
- Méthode d'authentification : API Key + API Secret + signature MD5
- Umeng se concentre sur le **suivi de l'efficacité promotionnelle**, différent des plateformes de diffusion publicitaire — il ne crée/gère pas directement les campagnes, mais trace les données de promotion par canal
- capabilities: `['report', 'oauth']`（ne prend pas en charge campaign/create/update/toggle）
- Interface de rapport : `/v1/ad_analytics/report` renvoie les données de promotion par canal/date
- fetchCampaigns renvoie vide（Umeng ne crée pas de campagnes）
- fetchReports récupère les données d'efficacité promotionnelle et les mappe au modèle de rapport unifié

### Algorithme de signature Umeng

```
sign = md5(method + url + body + api_secret)
```

L'authentification passe par les en-têtes HTTP : `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Points clés du mapping des champs :**
- `channel` → `platform_campaign_id`（l'identifiant de canal est mappé à la dimension campagne）
- `pv` → `impressions`（impressions）
- `click` → `clicks`（clics）
- `activation` → `conversions`（activations/conversions）
- Unité de `cost` : yuan → fen (×100)

---

## Task 15 : Créer l'adaptateur 快手磁力引擎

**Fichiers :**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spécifications de l'adaptateur

快手磁力引擎 (Kwai Ads) :
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- Méthode d'authentification : `access_token` en en-tête
- Campagnes : `/campaign/list` + `/campaign/create` + `/campaign/update`
- Rapports : `/report/campaign/report` (retour synchrone)
- Unité monétaire : yuan → fen (×100)

**Points clés du mapping des champs :**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget`（yuan→fen ×100）
- `put_status` → `status`（1→enabled, 2→paused, 3→deleted）
- Dans les rapports : `charge`→`cost`（yuan→fen）/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16 : Créer l'adaptateur 小红书蒲公英

**Fichiers :**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Spécifications de l'adaptateur

小红书蒲公英 (plateforme 聚光 Xiaohongshu) :
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- Méthode d'authentification : `access_token` en en-tête (`Authorization: Bearer xxx`)
- Campagnes : `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Rapports : `/v1/report/campaign/report`
- Unité monétaire : fen（l'API Xiaohongshu renvoie les fen, aucune conversion）
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Points clés du mapping des champs :**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget`（unité : fen）
- `status` → `status`（`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted）
- Dans les rapports : `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Critères d'acceptation

1. ✅ L'adaptateur 腾讯广告 implémente les 13 méthodes PlatformAdapter
2. ✅ L'adaptateur 友盟 implémente les capacités report + oauth（Umeng ne prend pas en charge les opérations de diffusion）
3. ✅ L'adaptateur 快手磁力引擎 implémente les 13 méthodes
4. ✅ L'adaptateur 小红书蒲公英 implémente les 13 méthodes
5. ✅ Les 4 adaptateurs sont enregistrés dans bootstrap.php
6. ✅ `GET /api/v1/platforms` renvoie 7 plateformes (y compris les 3 précédentes)
7. ✅ Tous les adaptateurs gèrent correctement les erreurs d'appel curl（curl_errno + CURLOPT_CONNECTTIMEOUT）
