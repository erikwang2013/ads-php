# Phase 3: Expansão dos Adaptadores de Plataformas de Publicidade — Plano de Implementação

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Goal:** Adicionar adaptadores para quatro plataformas: Tencent Ads, Umeng, Kuaishou Magnet Engine e Xiaohongshu Dandelion.

**Adaptadores existentes (Fases 1+2):** Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama

**Arquitetura:** Cada adaptador implementa a interface `PlatformAdapter` e é registrado no `AdapterRegistry`, podendo ser chamado de forma unificada pelo fluxo de autorização OAuth, pelas tarefas de sincronização de dados e pelo painel administrativo do frontend.

---

## Task 13: Criar o adaptador do Tencent Ads

**Arquivos:**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Especificação do adaptador

API do Tencent Ads (Guangdiantong):
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- Autenticação: `access_token` como parâmetro de URL + `nonce`/`timestamp` contra replay
- Planos de publicidade: `campaigns/get` + `campaigns/add` + `campaigns/update`
- Relatórios: `daily_reports/get` (assíncrono: criar tarefa → consultar → obter)
- Unidade monetária: centavos (consistente com o modelo unificado, sem conversão)
- Mapeamento de status: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Assinatura específica da API do Tencent

O Tencent usa `access_token` como parâmetro de URL; não requer assinatura MD5, mas exige `nonce` (número aleatório) + `timestamp` para proteção contra replay.

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

**Pontos-chave do mapeamento de campos:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (unidade já em centavos, sem conversão)
- `configured_status` → `status`（AD_STATUS_NORMAL/SUSPEND/DELETE）
- No relatório: `cost` (centavos)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: Criar o adaptador da Umeng

**Arquivos:**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Especificação do adaptador

Umeng (U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- Autenticação: API Key + API Secret + assinatura MD5
- A Umeng foca em **monitoramento do efeito de promoção**, diferente das plataformas de veiculação de anúncios — ela não cria/gerencia planos de publicidade diretamente, mas rastreia os dados de promoção de cada canal
- capabilities: `['report', 'oauth']` (não suporta campaign/create/update/toggle)
- Endpoint de relatório: `/v1/ad_analytics/report` retorna os dados de promoção por canal/data
- fetchCampaigns retorna vazio (a Umeng não cria planos próprios)
- fetchReports busca os dados de efeito de promoção e os mapeia para o modelo de relatório unificado

### Algoritmo de assinatura da Umeng

```
sign = md5(method + url + body + api_secret)
```

As informações de autenticação são enviadas via cabeçalhos HTTP: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Pontos-chave do mapeamento de campos:**
- `channel` → `platform_campaign_id` (o identificador do canal é mapeado para a dimensão de plano)
- `pv` → `impressions` (impressões)
- `click` → `clicks` (cliques)
- `activation` → `conversions` (ativações/conversões)
- Unidade de `cost`: yuan → centavos (×100)

---

## Task 15: Criar o adaptador do Kuaishou Magnet Engine

**Arquivos:**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Especificação do adaptador

Kuaishou Magnet Engine (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- Autenticação: cabeçalho `access_token`
- Planos de publicidade: `/campaign/list` + `/campaign/create` + `/campaign/update`
- Relatórios: `/report/campaign/report` (retorno síncrono)
- Unidade monetária: yuan → centavos (×100)

**Pontos-chave do mapeamento de campos:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (yuan→centavos ×100)
- `put_status` → `status`（1→enabled, 2→paused, 3→deleted）
- No relatório: `charge`→`cost` (yuan→centavos)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: Criar o adaptador do Xiaohongshu Dandelion

**Arquivos:**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Especificação do adaptador

Xiaohongshu Dandelion (plataforma Juguang do Xiaohongshu):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- Autenticação: cabeçalho `access_token` (`Authorization: Bearer xxx`)
- Planos de publicidade: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Relatórios: `/v1/report/campaign/report`
- Unidade monetária: centavos (a API do Xiaohongshu retorna centavos, sem conversão)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Pontos-chave do mapeamento de campos:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (unidade: centavos)
- `status` → `status`（`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted）
- No relatório: `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Critérios de aceitação

1. ✅ O adaptador do Tencent Ads implementa todos os 13 métodos do PlatformAdapter
2. ✅ O adaptador da Umeng implementa as capacidades report + oauth (a Umeng não suporta operações de veiculação)
3. ✅ O adaptador do Kuaishou Magnet Engine implementa todos os 13 métodos
4. ✅ O adaptador do Xiaohongshu Dandelion implementa todos os 13 métodos
5. ✅ Os 4 adaptadores estão registrados no bootstrap.php
6. ✅ `GET /api/v1/platforms` retorna 7 plataformas (incluindo as 3 anteriores)
7. ✅ Todos os adaptadores tratam erros corretamente nas chamadas curl (curl_errno + CURLOPT_CONNECTTIMEOUT)

