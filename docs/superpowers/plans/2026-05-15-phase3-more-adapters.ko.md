# Phase 3: 광고 플랫폼 어댑터 확장 구현 계획

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **에이전트 워커용:** 필수 하위 스킬: superpowers:subagent-driven-development를 사용하여 구현하세요.

**목표:** 텐센트 광고, 우맹(友盟), 콰이서우 마그네틱 엔진, 샤오홍슈 민들레 4개 플랫폼의 어댑터를 추가합니다.

**기존 어댑터 (Phase 1+2):** 巨量引擎(줄량 엔진), 바이두 마케팅, 타오바오/알리마마

**아키텍처:** 각 어댑터가 `PlatformAdapter` 인터페이스를 구현하고 `AdapterRegistry`에 등록하면, OAuth 인증 흐름, 데이터 동기화 작업, 프론트엔드 관리 백엔드에서 통일적으로 호출할 수 있습니다.

---

## Task 13: 텐센트 광고 어댑터 생성

**파일:**
- 생성: `service/plugin/ads-platform/adapter/Tencent.php`
- 수정: `service/plugin/ads-platform/config/bootstrap.php`

### 어댑터 사양

텐센트 광고(광뎬퉁, 广点通) API:
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- 인증 방식: `access_token` URL 파라미터 + `nonce`/`timestamp` 리플레이 방지
- 광고 계획: `campaigns/get` + `campaigns/add` + `campaigns/update`
- 보고서: `daily_reports/get` (비동기: 작업 생성→폴링→조회)
- 금액 단위: 分(펀) (통일 모델과 일치, 변환 불필요)
- 상태 매핑: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### 텐센트 고유 API 서명

텐센트는 `access_token`을 URL 파라미터로 사용하며 MD5 서명은 필요 없지만, 리플레이 방지를 위한 `nonce`(난수) + `timestamp`가 필요합니다.

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

**필드 매핑 요점:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget`(단위 이미 分, 변환 불필요)
- `configured_status` → `status`(AD_STATUS_NORMAL/SUSPEND/DELETE)
- 보고서의 `cost`(分)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: 우맹 어댑터 생성

**파일:**
- 생성: `service/plugin/ads-platform/adapter/Umeng.php`
- 수정: `service/plugin/ads-platform/config/bootstrap.php`

### 어댑터 사양

우맹(Umeng U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- 인증 방식: API Key + API Secret + MD5 서명
- 우맹은 **프로모션 효과 모니터링**에 특화되어 있으며, 광고 집행 플랫폼과 다릅니다 — 광고 계획을 직접 생성/관리하지 않고 각 채널의 프로모션 데이터를 추적합니다
- capabilities: `['report', 'oauth']` (campaign/create/update/toggle 미지원)
- 보고서 API: `/v1/ad_analytics/report`가 채널/날짜 차원의 프로모션 데이터 반환
- fetchCampaigns는 빈 배열 반환(우맹은 계획 자체 생성 안 함)
- fetchReports는 프로모션 효과 데이터를 가져와 통일 보고서 모델에 매핑

### 우맹 서명 알고리즘

```
sign = md5(method + url + body + api_secret)
```

HTTP Header로 인증 정보 전달: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**필드 매핑 요점:**
- `channel` → `platform_campaign_id`(채널 식별자를 계획 차원에 매핑)
- `pv` → `impressions`(노출)
- `click` → `clicks`(클릭)
- `activation` → `conversions`(활성화/전환)
- `cost` 단위: 元 → 分 (×100)

---

## Task 15: 콰이서우 마그네틱 엔진 어댑터 생성

**파일:**
- 생성: `service/plugin/ads-platform/adapter/Kuaishou.php`
- 수정: `service/plugin/ads-platform/config/bootstrap.php`

### 어댑터 사양

콰이서우 마그네틱 엔진(Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- 인증 방식: `access_token` Header
- 광고 계획: `/campaign/list` + `/campaign/create` + `/campaign/update`
- 보고서: `/report/campaign/report` (동기 반환)
- 금액 단위: 元 → 分 (×100)

**필드 매핑 요점:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget`(元→分 ×100)
- `put_status` → `status`(1→enabled, 2→paused, 3→deleted)
- 보고서의 `charge`→`cost`(元→分)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: 샤오홍슈 민들레 어댑터 생성

**파일:**
- 생성: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- 수정: `service/plugin/ads-platform/config/bootstrap.php`

### 어댑터 사양

샤오홍슈 민들레(샤오홍슈 쥐광 플랫폼):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- 인증 방식: `access_token` Header (`Authorization: Bearer xxx`)
- 광고 계획: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- 보고서: `/v1/report/campaign/report`
- 금액 단위: 分(샤오홍슈 API는 分을 반환하므로 변환 불필요)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**필드 매핑 요점:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget`(단위: 分)
- `status` → `status`(`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- 보고서의 `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## 수용 기준

1. ✅ 텐센트 광고 어댑터가 PlatformAdapter 13개 메서드 전부 구현
2. ✅ 우맹 어댑터가 report + oauth 능력 구현(우맹은 집행 작업 미지원)
3. ✅ 콰이서우 마그네틱 엔진 어댑터가 13개 메서드 전부 구현
4. ✅ 샤오홍슈 민들레 어댑터가 13개 메서드 전부 구현
5. ✅ 4개 어댑터 모두 bootstrap.php에 등록
6. ✅ `GET /api/v1/platforms`가 7개 플랫폼 반환(기존 3개 포함)
7. ✅ 모든 어댑터 curl 호출에 올바른 오류 처리(curl_errno + CURLOPT_CONNECTTIMEOUT)
