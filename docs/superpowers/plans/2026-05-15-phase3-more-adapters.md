# Phase 3: 扩展广告平台适配器 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Goal:** 新增腾讯广告、友盟、快手磁力引擎、小红书蒲公英四个平台的适配器。

**已有适配器（Phase 1+2）：** 巨量引擎、百度营销、淘宝/阿里妈妈

**Architecture:** 每个适配器实现 `PlatformAdapter` 接口，注册到 `AdapterRegistry`，即可被 OAuth 授权流程、数据同步任务、前端管理后台统一调用。

---

## Task 13: 创建腾讯广告适配器

**文件：**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### 适配器规格

腾讯广告（广点通）API：
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- 认证方式: `access_token` URL参数 + `nonce`/`timestamp` 防重放
- 广告计划: `campaigns/get` + `campaigns/add` + `campaigns/update`
- 报表: `daily_reports/get` (异步：创建任务→轮询→获取)
- 金额单位：分（与统一模型一致，无需转换）
- 状态映射：`AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### 腾讯特有 API 签名

腾讯使用 `access_token` 作为 URL 参数，不需要 MD5 签名，但需要 `nonce`（随机数）+ `timestamp` 防重放。

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

**字段映射要点：**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget`（单位已是分，无需转换）
- `configured_status` → `status`（AD_STATUS_NORMAL/SUSPEND/DELETE）
- 报表中 `cost`（分）/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: 创建友盟适配器

**文件：**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### 适配器规格

友盟（Umeng U-App + U-Ads）：
- API Base: `https://api.open.umeng.com/`
- 认证方式: API Key + API Secret + MD5 签名
- 友盟侧重**推广效果监测**，与广告投放平台不同——它不直接创建/管理广告计划，而是追踪各渠道推广数据
- capabilities: `['report', 'oauth']` （不支持 campaign/create/update/toggle）
- 报表接口：`/v1/ad_analytics/report` 返回按渠道/日期维度的推广数据
- fetchCampaigns 返回空（友盟不自建计划）
- fetchReports 拉取推广效果数据映射到统一报表模型

### 友盟签名算法

```
sign = md5(method + url + body + api_secret)
```

通过 HTTP Header 传认证信息：`X-Umeng-API-Key`、`X-Umeng-Sign`、`X-Umeng-Timestamp`。

**字段映射要点：**
- `channel` → `platform_campaign_id`（渠道标识映射到计划维度）
- `pv` → `impressions`（展示）
- `click` → `clicks`（点击）
- `activation` → `conversions`（激活/转化）
- `cost` 单位：元 → 分 (×100)

---

## Task 15: 创建快手磁力引擎适配器

**文件：**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### 适配器规格

快手磁力引擎（Kwai Ads）：
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- 认证方式: `access_token` Header
- 广告计划: `/campaign/list` + `/campaign/create` + `/campaign/update`
- 报表: `/report/campaign/report` (同步返回)
- 金额单位：元 → 分 (×100)

**字段映射要点：**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget`（元→分 ×100）
- `put_status` → `status`（1→enabled, 2→paused, 3→deleted）
- 报表中 `charge`→`cost`（元→分）/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: 创建小红书蒲公英适配器

**文件：**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### 适配器规格

小红书蒲公英（小红书聚光平台）：
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- 认证方式: `access_token` Header (`Authorization: Bearer xxx`)
- 广告计划: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- 报表: `/v1/report/campaign/report`
- 金额单位：分（小红书API返回分，无需转换）
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**字段映射要点：**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget`（单位：分）
- `status` → `status`（`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted）
- 报表中 `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## 验收标准

1. ✅ 腾讯广告适配器实现全部 13 个 PlatformAdapter 方法
2. ✅ 友盟适配器实现 report + oauth 能力（友盟不支持投放操作）
3. ✅ 快手磁力引擎适配器实现全部 13 个方法
4. ✅ 小红书蒲公英适配器实现全部 13 个方法
5. ✅ 4个适配器均在 bootstrap.php 注册
6. ✅ `GET /api/v1/platforms` 返回 7 个平台（含之前3个）
7. ✅ 所有适配器 curl 调用正确错误处理（curl_errno + CURLOPT_CONNECTTIMEOUT）
