# Phase 3: 广告平台适配器の拡張 Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Goal:** 腾讯广告、友盟、快手磁力引擎、小红书蒲公英の 4 プラットフォームのアダプターを新規追加します。

**既存アダプター（Phase 1+2）：** 巨量引擎、百度营销、淘宝/阿里妈妈

**Architecture:** 各アダプターは `PlatformAdapter` インターフェースを実装し、`AdapterRegistry` に登録すると、OAuth 認証フロー、データ同期タスク、フロントエンド管理バックエンドから統一的に呼び出せます。

---

## Task 13: 腾讯广告アダプターの作成

**ファイル：**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### アダプター仕様

腾讯广告（広点通）API：
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- 認証方式: `access_token` URL パラメータ + `nonce`/`timestamp` によるリプレイ防止
- 広告計画: `campaigns/get` + `campaigns/add` + `campaigns/update`
- レポート: `daily_reports/get` (非同期：タスク作成→ポーリング→取得)
- 金額単位：分（統一モデルと一致、変換不要）
- 状態マッピング：`AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### 腾讯固有の API 署名

腾讯は `access_token` を URL パラメータとして使用し、MD5 署名は不要ですが、`nonce`（乱数）+ `timestamp` によるリプレイ防止が必要です。

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

**フィールドマッピングの要点：**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget`（単位は分のまま、変換不要）
- `configured_status` → `status`（AD_STATUS_NORMAL/SUSPEND/DELETE）
- レポート内 `cost`（分）/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: 友盟アダプターの作成

**ファイル：**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### アダプター仕様

友盟（Umeng U-App + U-Ads）：
- API Base: `https://api.open.umeng.com/`
- 認証方式: API Key + API Secret + MD5 署名
- 友盟は**プロモーション効果の計測**に特化しており、広告配信プラットフォームとは異なります——広告計画を直接作成/管理するのではなく、各チャネルのプロモーションデータを追跡します
- capabilities: `['report', 'oauth']` （campaign/create/update/toggle はサポート外）
- レポート API：`/v1/ad_analytics/report` はチャネル/日付次元のプロモーションデータを返却
- fetchCampaigns は空を返却（友盟は計画を自前で作成しない）
- fetchReports でプロモーション効果データを取得し、統一レポートモデルにマッピング

### 友盟の署名アルゴリズム

```
sign = md5(method + url + body + api_secret)
```

HTTP Header で認証情報を送信：`X-Umeng-API-Key`、`X-Umeng-Sign`、`X-Umeng-Timestamp`。

**フィールドマッピングの要点：**
- `channel` → `platform_campaign_id`（チャネル識別子を計画次元にマッピング）
- `pv` → `impressions`（表示）
- `click` → `clicks`（クリック）
- `activation` → `conversions`（アクティベーション/コンバージョン）
- `cost` 単位：元 → 分 (×100)

---

## Task 15: 快手磁力引擎アダプターの作成

**ファイル：**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### アダプター仕様

快手磁力引擎（Kwai Ads）：
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- 認証方式: `access_token` Header
- 広告計画: `/campaign/list` + `/campaign/create` + `/campaign/update`
- レポート: `/report/campaign/report` (同期返却)
- 金額単位：元 → 分 (×100)

**フィールドマッピングの要点：**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget`（元→分 ×100）
- `put_status` → `status`（1→enabled, 2→paused, 3→deleted）
- レポート内 `charge`→`cost`（元→分）/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: 小红书蒲公英アダプターの作成

**ファイル：**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### アダプター仕様

小红书蒲公英（小红书聚光平台）：
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- 認証方式: `access_token` Header (`Authorization: Bearer xxx`)
- 広告計画: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- レポート: `/v1/report/campaign/report`
- 金額単位：分（小红书 API は分を返却、変換不要）
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**フィールドマッピングの要点：**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget`（単位：分）
- `status` → `status`（`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted）
- レポート内 `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## 受入基準

1. ✅ 腾讯广告アダプターが全 13 個の PlatformAdapter メソッドを実装
2. ✅ 友盟アダプターが report + oauth 能力を実装（友盟は配信操作をサポートしない）
3. ✅ 快手磁力引擎アダプターが全 13 メソッドを実装
4. ✅ 小红书蒲公英アダプターが全 13 メソッドを実装
5. ✅ 4 アダプターすべて bootstrap.php に登録
6. ✅ `GET /api/v1/platforms` が 7 プラットフォームを返却（従来の 3 つを含む）
7. ✅ 全アダプターの curl 呼び出しが正しいエラーハンドリング（curl_errno + CURLOPT_CONNECTTIMEOUT）
