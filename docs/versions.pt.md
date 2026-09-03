# Comparação de versões

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Versão | Licença | Como obter |
|------|------|----------|
| **Lite** | Open source (MIT) | Repositório público do GitHub |
| **Standard** | Licença comercial | Contate erik@erik.xyz |
| **Full** | Licença comercial | Contate erik@erik.xyz |

---

## Comparação de funcionalidades

### Funcionalidades básicas

| Funcionalidade | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Autenticação (login/refresh de Token/usuário atual) | ✅ | ✅ | ✅ |
| Gerenciamento de plataformas (lista de 29 plataformas + OAuth) | ✅ | ✅ | ✅ |
| Gerenciamento de contas (CRUD + sincronização) | ✅ | ✅ | ✅ |
| Planos de anúncios (CRUD + iniciar/pausar + em lote) | ✅ | ✅ | ✅ |
| Relatórios (dashboard + personalizado + exportação CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Verificação de saúde + documentação da API + captcha | ✅ | ✅ | ✅ |
| Sincronização de dados (Campaign + Report) | ✅ | ✅ | ✅ |

### Gerenciamento de veiculação

| Funcionalidade | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Grupos de anúncios (CRUD + iniciar/pausar) | — | ✅ | ✅ |
| Criativos (lista + detalhes) | — | ✅ | ✅ |
| Sincronização de dados de grupos de anúncios/criativos | — | ✅ | ✅ |

### Monitoramento e notificações

| Funcionalidade | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Engine de regras de alerta (7 métricas/4 condições/3 escopos) | — | ✅ | ✅ |
| Registros de alertas + confirmação + não lidos | — | ✅ | ✅ |
| Central de notificações (lista/lidas/todas lidas) | — | ✅ | ✅ |

### Funcionalidades avançadas

| Funcionalidade | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Engine de regras de lance automático (3 ações/resfriamento) | — | — | ✅ |
| Modelos de segmentação de público (JSON Schema genérico) | — | — | ✅ |
| Biblioteca de materiais de anúncios (upload/galeria/prévia) | — | — | ✅ |
| Alerta de orçamento (alertas em três níveis 50/80/100%) | — | — | ✅ |
| Calendário de veiculação (visualização Gantt) | — | — | ✅ |
| Atribuição entre plataformas (5 modelos/retrospectiva de 30 dias) | — | — | ✅ |

---

## Comparação de proteções de segurança

| Item de proteção | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| Lista de permissões CORS | ✅ | ✅ | ✅ |
| Cabeçalhos de segurança (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Roteamento por versão (/api/v1) | ✅ | ✅ | ✅ |
| Rate limit de API (janela deslizante) | ✅ | ✅ | ✅ |
| Detecção de injeção de SQL (correspondência de padrões) | ✅ | ✅ | ✅ |
| Filtragem de entrada (strip_tags + trim) | ✅ | ✅ | ✅ |
| Criptografia de transporte (X-Encrypted) | ✅ | ✅ | ✅ |
| Autenticação JWT Bearer | ✅ | ✅ | ✅ |
| Detecção de ataques XSS (11 padrões) | — | ✅ | ✅ |
| Detecção de path traversal (7 padrões) | — | ✅ | ✅ |
| Detecção de injeção de Header | — | ✅ | ✅ |
| Limite de tamanho do body (10 MiB) | — | ✅ | ✅ |
| Lista de permissões Content-Type | — | ✅ | ✅ |
| Identificação da origem do cliente (8 plataformas) | — | ✅ | ✅ |
| Throttle de login (5 falhas→15 minutos) | — | ✅ | ✅ |
| Monitoramento do tempo de resposta (X-Response-Time) | — | ✅ | ✅ |
| Validação Origin/Referer | — | — | ✅ |
| Anti-replay (Nonce+Timestamp) | — | — | ✅ |
| Limite de sessões concorrentes (máximo de 3) | — | — | ✅ |
| CSRF Token (lado Admin) | — | — | ✅ |
| Proteção SSRF (lista de permissões OAuth) | — | — | ✅ |
| Mascaramento de dados em logs | — | — | ✅ |
| Vínculo JWT IP/UA | — | — | ✅ |

---

## Comparação das cadeias de middleware

### Lado Service

| Lite (7 camadas) | Standard (11 camadas) | Full (15 camadas) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Lado Admin

| Lite (1 camada) | Standard (4 camadas) | Full (5 camadas) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Comparação de tarefas agendadas

| Tarefa | Frequência | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (apenas Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## Comparação de tabelas do banco de dados

| Categoria | Nome da tabela | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| Base | ads_tenants | ✅ | ✅ | ✅ |
| Contas | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| Veiculação | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| Alertas | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| Notificações | ads_notifications | — | ✅ | ✅ |
| Lances | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| Segmentação | ads_targeting_templates | — | — | ✅ |
| Materiais | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| Atribuição | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| Sistema | ads_sync_errors | ✅ | ✅ | ✅ |
| Administração | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Total** | | **8** | **13** | **19** |

---

## Comparação de páginas do frontend

### Vue Admin SPA

| Página | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Lista de contas + vínculo | ✅ | ✅ | ✅ |
| Planos de anúncios | ✅ | ✅ | ✅ |
| Exportação de relatórios | ✅ | ✅ | ✅ |
| Gerenciamento de usuários | ✅ | ✅ | ✅ |
| Logs de auditoria | ✅ | ✅ | ✅ |
| Grupos de anúncios | — | ✅ | ✅ |
| Criativos | — | ✅ | ✅ |
| Análise de relatórios (ECharts) | — | ✅ | ✅ |
| Regras de alerta | — | ✅ | ✅ |
| Registros de alertas | — | ✅ | ✅ |
| Central de notificações | — | ✅ | ✅ |
| Lance automático | — | — | ✅ |
| Biblioteca de materiais | — | — | ✅ |
| Provedores CDN | — | — | ✅ |
| Calendário de veiculação | — | — | ✅ |
| Análise de atribuição | — | — | ✅ |
| **Total** | **7** | **13** | **18** |

### Flutter

| Página | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Planos de anúncios (lista+detalhes) | ✅ | ✅ | ✅ |
| Relatórios de dados | ✅ | ✅ | ✅ |
| Contas de plataforma | ✅ | ✅ | ✅ |
| Gerenciamento de alertas | ✅ | ✅ | ✅ |
| Grupos de anúncios | — | ✅ | ✅ |
| Criativos | — | ✅ | ✅ |
| Análise de relatórios | — | ✅ | ✅ |
| Central de notificações | — | ✅ | ✅ |
| Lance automático | — | — | ✅ |
| **Total** | **6** | **10** | **11** |

---

## Comparação de endpoints da API

| Módulo | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Sistema (health/ping/docs/captcha) | 6 | 6 | 6 |
| Autenticação (login/me/refresh) | 3 | 3 | 3 |
| Plataformas (list/oauthUrl/callback) | 3 | 3 | 3 |
| Contas (index/show/destroy/sync) | 4 | 4 | 4 |
| Planos de anúncios (CRUD/toggle/batch) | 6 | 6 | 6 |
| Grupos de anúncios (CRUD/toggle) | — | 5 | 5 |
| Criativos (index/show) | — | 2 | 2 |
| Relatórios (summary/custom/export×2) | 4 | 4 | 4 |
| Relatórios (calendar/budget/attribution/models) | — | — | 4 |
| Alertas (CRUD de rules + logs + acknowledge + unread) | — | 7 | 7 |
| Notificações (index/unread/read/readAll) | — | 4 | 4 |
| Lance automático (CRUD + logs) | — | — | 5 |
| Modelos de segmentação (CRUD) | — | — | 5 |
| Biblioteca de materiais (index/upload/show/destroy/presign/register) | — | — | 6 |
| Provedores CDN (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **Total** | **26** | **44** | **70** |

---

## Stack de tecnologia

As três versões compartilham uma stack de tecnologia unificada:

| Camada | Tecnologia |
|----|------|
| Framework backend | webman v2, PHP 8.2+ |
| Banco de dados | MySQL 8.0 (InnoDB, utf8mb4) |
| Cache | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Autenticação | erikwang2013/jwt-webman |
| Geração de ID | erikwang2013/snowflake-php |
| Codificação de ID | erikwang2013/hashids |
| Frontend | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Implantação | Docker + Nginx + Docker Compose |

---

## Caminho de upgrade

```
Lite (开源)
  │
  ├─→ 升级到 Standard (联系 erik@erik.xyz)
  │     │
  │     └─→ 新增: 广告组/创意管理、告警引擎、通知中心、
  │              AttackGuard/XSS/路径遍历/登录节流/响应时间监控
  │
  └─→ 升级到 Full (联系 erik@erik.xyz)
        │
        └─→ 新增: Standard 全部 + 自动出价、定向模板、素材库、
                  预算预警、投放日历、跨平台归因、防重放/并发限制/CSRF/SSRF
```

