# Documentação da API

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **Documentação online hg/apidoc**: após iniciar o serviço, acesse `http://127.0.0.1:8788/apidoc` (alternância entre os dois aplicativos Service + Admin)  
> Arquivo de configuração: `service/config/plugin/hg/apidoc/app.php`

---

## Especificações gerais

### Base URL

```
http://your-domain.com/api/v1
```

> O número da versão da API é fixado no caminho da URL (atualmente `v1`) e não é transmitido em um Header; versões principais futuras, como `/api/v2`, seguem a mesma regra.

### Headers obrigatórios

| Header | Valor | Descrição |
|--------|----|------|
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Plataforma de origem da operação (obrigatório) |
| `Authorization` | `Bearer <token>` | Token de autenticação JWT (obrigatório exceto login/lista de plataformas/verificação de saúde) |

### Headers anti-replay (fora do navegador)

| Header | Descrição |
|--------|------|
| `X-Nonce` | String aleatória (única por requisição) |
| `X-Timestamp` | Timestamp Unix em segundos (janela de ±5 minutos) |

### Headers opcionais

| Header | Descrição |
|--------|------|
| `X-Tenant-Id` | ID do tenant (modo multitenant) |
| `X-Encrypted` | `1` = corpo da requisição precisa ser descriptografado e o da resposta criptografado |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Valor | Descrição |
|----|------|
| `application/json` | Corpo de requisição JSON (recomendado) |
| `application/x-www-form-urlencoded` | Requisição de formulário |
| `multipart/form-data` | Upload de arquivos |

### Formato de resposta

**Sucesso**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Paginação**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**Erro**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**Verificação de saúde**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### Códigos de status HTTP

| Código | Significado |
|--------|------|
| 200 | Sucesso |
| 204 | Preflight OPTIONS bem-sucedido |
| 400 | Parâmetros de requisição inválidos, versão de API não suportada |
| 401 | Não autenticado, Token expirado, IP/UA do Token não correspondente |
| 403 | Acesso proibido (XSS/path traversal/CSRF/injeção de SQL/Origin não correspondente) |
| 404 | Recurso não encontrado |
| 429 | Muitas requisições (rate limit/throttle de login/limite de sessões concorrentes) |
| 500 | Erro do servidor |
| 503 | Serviço degradado (DB ou Redis indisponível) |

### Parâmetros de paginação

| Parâmetro | Padrão | Máximo | Descrição |
|------|--------|--------|------|
| `page` | 1 | — | Número da página |
| `per_page` | 20 | 100 | Itens por página (truncado automaticamente se exceder) |
| `sort` | `id` | — | Campo de ordenação (deve estar na lista de permissões) |

### Estratégia de cache

| Endpoint | TTL | Camada |
|------|-----|-----|
| `/api/v1/platforms` | 1 hora | L1 memória → L2 APCu → L3 Redis |
| `/api/v1/accounts` + `/api/v1/accounts/:id` | 5 minutos | O mesmo acima |
| `/api/v1/reports/summary` | 5 minutos | O mesmo acima |
| `/api/v1/alerts/rules` | 2 minutos | O mesmo acima |
| `/api/v1/alerts/unread-count` | 30 segundos | O mesmo acima |

---

## Módulo 1: Sistema

### GET /health — verificação de saúde

```
GET /health
```

**Resposta**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) ou `degraded` (503)
- Sem requisito de autenticação, não passa pelo roteamento de versão

---

### GET /ping — verificação de atividade

```
GET /ping
```

**Resposta**: `{ "pong": true }`

---

### GET /docs — documentação da API

```
GET /docs
```

Retorna a página de documentação da API em HTML (sem autenticação).

---

### GET /api/v1/captcha/generate — gerar captcha

Sem autenticação.

**Resposta**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- Token válido por 5 minutos
- Tolerância de deslocamento de 5px

---

### POST /api/v1/captcha/verify — verificar captcha

Sem autenticação.

**Requisição**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Resposta**: `{ "code": 0, "message": "Verificação aprovada" }`

---

## Módulo 2: Autenticação

### POST /api/v1/auth/login — login

Sem autenticação.

**Requisição**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**Resposta**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- JWT Token válido por 24 horas
- Token incorpora hash de IP + User-Agent
- 5 falhas → bloqueio no Redis por 15 minutos

---

### GET /api/v1/auth/me — usuário atual

**Cabeçalho da requisição**: `Authorization: Bearer <token>`

**Resposta**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/v1/auth/refresh — atualizar Token

**Cabeçalho da requisição**: `Authorization: Bearer <old_token>`

**Resposta**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- Token antigo é adicionado automaticamente à lista negra
- Máximo de 3 Tokens ativos por usuário

---

## Módulo 3: Plataformas & contas

### GET /api/v1/platforms — lista de plataformas

Sem autenticação. Cache de 1 hora.

**Resposta**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/v1/platforms/:code/oauth-url — URL de autorização OAuth

**Parâmetro**: `?redirect_uri=https://your-domain.com/callback`

**Resposta**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` deve passar pela validação da lista de permissões SSRF (variável de ambiente `OAUTH_ALLOWED_REDIRECTS`)

---

### POST /api/v1/platforms/:code/callback — callback OAuth

**Requisição**: `{ "state": "...", "code": "..." }`

**Resposta**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/v1/accounts — lista de contas

Cache de 5 minutos.

**Parâmetros**:

| Parâmetro | Descrição |
|------|------|
| `platform` | Filtro por código de plataforma |
| `page` | Número da página |
| `per_page` | Itens por página |

**Resposta**: formato paginado; cada item de `list` contém `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/v1/accounts/:id — detalhes da conta

Cache de 5 minutos.

---

### DELETE /api/v1/accounts/:id — desvincular conta

---

### POST /api/v1/accounts/:id/sync — sincronização manual

---

## Módulo 4: Planos de anúncios

### GET /api/v1/campaigns — lista de planos

**Parâmetros**:

| Parâmetro | Descrição | Valores possíveis |
|------|------|--------|
| `platform` | Filtro de plataforma | juliang, meta, google... |
| `status` | Filtro de status | enabled, paused |
| `keyword` | Busca por nome | Qualquer texto |
| `sort` | Campo de ordenação | id, name, platform, daily_budget, status, created_at |
| `page` | Número da página | — |
| `per_page` | Itens por página | ≤100 |

**Resposta**: formato paginado + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/v1/campaigns — criar plano

**Requisição**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Resposta**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- Unidade de `daily_budget`: centavos (20000 = ¥200.00)

---

### GET /api/v1/campaigns/:id — detalhes do plano

**Resposta**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/v1/campaigns/:id — atualizar plano

**Requisição**: `{ "name": "Novo nome", "daily_budget": 30000 }`

---

### POST /api/v1/campaigns/:id/toggle — iniciar/pausar plano

**Requisição**: `{ "enabled": false }`

---

### POST /api/v1/campaigns/batch/toggle — iniciar/pausar em lote

**Requisição**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Resposta**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Módulo 5: Grupos de anúncios

### GET /api/v1/ad-groups — lista de grupos de anúncios

**Parâmetros**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/v1/ad-groups — criar grupo de anúncios

**Requisição**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: opcional, carrega o targeting JSON do modelo de segmentação e faz merge

### GET /api/v1/ad-groups/:id — detalhes do grupo de anúncios

### PUT /api/v1/ad-groups/:id — atualizar grupo de anúncios

### POST /api/v1/ad-groups/:id/toggle — iniciar/pausar grupo de anúncios

---

## Módulo 6: Criativos

### GET /api/v1/creatives — lista de criativos

**Parâmetros**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/v1/creatives/:id — detalhes do criativo

---

## Módulo 7: Relatórios

### GET /api/v1/reports/summary — resumo do dashboard

Cache de 5 minutos.

**Parâmetros**: `date_start`, `date_end`

**Resposta**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/v1/reports/custom — relatório personalizado

**Parâmetros**:

| Parâmetro | Descrição |
|------|------|
| `dimensions[]` | Dimensões: date, platform, campaign |
| `metrics[]` | Métricas: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Data inicial |
| `date_end` | Data final |
| `platform` | Filtro de plataforma |

---

### GET /api/v1/reports/export — exportar relatório

**Parâmetros**: `format=csv`, `date_start`, `date_end`, `metrics[]`

Retorna download de arquivo (CSV UTF-8 BOM ou Excel .xls).

---

### GET /api/v1/reports/export-dashboard — exportar dashboard em PDF

---

### GET /api/v1/reports/calendar — calendário de veiculação

**Parâmetros**: `date_start`, `date_end`, `platform`

**Resposta**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/v1/reports/budget-alerts — alertas de orçamento

**Resposta**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/v1/reports/attribution — análise de atribuição

**Parâmetros**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Resposta**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/v1/reports/attribution/models — lista de modelos de atribuição

**Resposta**: `[{ code: "last_touch", name: "Último contato", description: "..." }]`

Existem 5 modelos no total.

---

## Módulo 8: Alertas

### GET /api/v1/alerts/rules — lista de regras de alerta

Cache de 2 minutos.

**Parâmetros**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/v1/alerts/rules — criar regra de alerta

**Requisição**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/v1/alerts/rules/:id — atualizar regra de alerta

### DELETE /api/v1/alerts/rules/:id — excluir regra de alerta

### GET /api/v1/alerts/logs — registros de alertas

**Parâmetros**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/v1/alerts/logs/:id/acknowledge — confirmar alerta

### GET /api/v1/alerts/unread-count — número de alertas não lidos

Cache de 30 segundos. Polling de 30s no frontend.

---

## Módulo 9: Notificações

### GET /api/v1/notifications — lista de notificações

**Parâmetros**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/v1/notifications/unread-count — número de notificações não lidas

### POST /api/v1/notifications/:id/read — marcar como lida

### POST /api/v1/notifications/read-all — marcar todas como lidas

---

## Módulo 10: Lance automático

### GET /api/v1/bid-rules — lista de regras

### POST /api/v1/bid-rules — criar regra

**Requisição**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**Descrição dos campos**:

| Campo | Tipo | Descrição |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Métrica monitorada |
| condition | gt/gte/lt/lte | Condição de disparo |
| threshold | decimal | Limite |
| action_type | adjust_budget/toggle_pause/toggle_enable | Tipo de ação |
| adjust_step | int (centavos) | Passo do ajuste de orçamento (positivo=adicionar, negativo=reduzir) |
| budget_min | int | Limite inferior do orçamento (centavos) |
| budget_max | int | Limite superior do orçamento (centavos) |
| cooldown_minutes | int | Tempo de resfriamento (padrão 60) |

### PUT /api/v1/bid-rules/:id — atualizar regra

### DELETE /api/v1/bid-rules/:id — excluir regra

### GET /api/v1/bid-rules/logs — histórico de lances

**Parâmetros**: `rule_id`, `campaign_id`

---

## Módulo 11: Modelos de segmentação

### GET /api/v1/targeting-templates — lista de modelos

**Parâmetros**: `platform`

### GET /api/v1/targeting-templates/:id — detalhes do modelo

### POST /api/v1/targeting-templates — criar modelo

**Requisição**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/v1/targeting-templates/:id — atualizar modelo

### DELETE /api/v1/targeting-templates/:id — excluir modelo

---

## Módulo 12: Biblioteca de materiais

### GET /api/v1/assets — lista de materiais

**Parâmetros**: `type`(image/video), `page`, `per_page`

### POST /api/v1/assets/upload — enviar material

**Requisição**: `multipart/form-data`, campo `file`

- Imagens: máximo de 5 MB (jpeg/png/gif/webp)
- Vídeos: máximo de 50 MB (mp4)

**Resposta**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- Com CDN configurado, `url` é montado com o `cdn_domain` do provedor padrão formando um endereço HTTPS completo

### POST /api/v1/assets/presign — Obter URL de upload pré-assinado

**Solicitação**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**Resposta**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- Formato de `key`: `Ymd/32hex.extensão`; devolver ao `/api/v1/assets/register` após o upload direto
- Para vídeos de até 50 MiB o cliente envia direto ao armazenamento de objetos; indisponível no driver `local`

### POST /api/v1/assets/register — Registrar material enviado diretamente

**Solicitação**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**Resposta**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` validado estritamente (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) contra path traversal

### GET /api/v1/assets/:id — detalhes do material

### DELETE /api/v1/assets/:id — excluir material

---

## Endpoints Admin (porta 8789)

### POST /api/v1/admin/login — login do administrador

**Requisição**: `{ "username": "admin", "password": "..." }`

**Resposta**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token armazenado em localStorage
- `csrf_token` deve ser enviado no header `X-CSRF-Token` das requisições POST/PUT/DELETE subsequentes

### GET /api/v1/admin/me — administrador atual

### POST /api/v1/admin/logout — sair

### GET /api/v1/admin/users — lista de usuários

**Parâmetros**: `keyword`, `role_id`, `page`, `per_page`

Na resposta, `id` e `role_id` são codificados com hashids.

### POST /api/v1/admin/users — criar usuário

### PUT /api/v1/admin/users/:id — atualizar usuário

### DELETE /api/v1/admin/users/:id — desabilitar usuário

### GET /api/v1/admin/users/roles — lista de papéis

### GET /api/v1/admin/audit-logs — logs de auditoria

**Parâmetros**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### Gestão de provedores CDN (somente tenant mestre da plataforma tenant 1, AdminMiddleware)

### GET /api/v1/admin/cdn/providers — Lista de provedores

### POST /api/v1/admin/cdn/providers — Criar provedor

**Solicitação**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, protocolo S3) / `s3` (compatível S3: AWS S3 / Cloudflare R2 / MinIO)
- Credenciais (access_key/secret_key/cdn_token) criptografadas por campo via Encryptable; respostas retornam apenas campos mascarados

### PUT /api/v1/admin/cdn/providers/:id — Atualizar provedor

### DELETE /api/v1/admin/cdn/providers/:id — Excluir (o padrão passa automaticamente ao próximo enabled)

### PUT /api/v1/admin/cdn/providers/:id/default — Definir como padrão

### PUT /api/v1/admin/cdn/providers/:id/toggle — Ativar/Desativar (ao desativar o padrão, ele é transferido automaticamente)

### POST /api/v1/admin/cdn/providers/:id/test — Teste de conectividade

**Resposta**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/v1/admin/cdn/providers/:id/purge — Purga de cache CDN

**Solicitação**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- Requer `cdn_driver` e `cdn_domain`; `aliyun` realmente implementado (assinatura OpenAPI), cloudflare/cloudfront pendentes

---

## Referência de códigos de erro

| code | HTTP | Descrição |
|------|------|------|
| 0 | 200 | Sucesso |
| 1 | 200/400 | Erro geral de negócio |
| 401 | 401 | Não autenticado / Token expirado / IP/UA não correspondente |
| 403 | 403 | Acesso proibido (interceptação de segurança) |
| 404 | 404 | Recurso não encontrado |
| 422 | 422 | Falha na validação de parâmetros |
| 429 | 429 | Muitas requisições / throttle de login / limite de concorrência |
| 1001 | 200 | Falha de autenticação (usuário ou senha incorretos) |

---

## Resposta de interceptação de segurança

Quando uma requisição é interceptada por um middleware de segurança, retorna 403:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Resposta de rate limit

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

O header `Retry-After` contém os segundos restantes de espera.

