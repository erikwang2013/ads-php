# Documento de design de funcionalidades

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Todas as definições das interfaces de API (requisição/resposta/parâmetros) estão em [api.md](api.pt.md).

---

## Visão geral dos módulos

| # | Módulo | Controlador/Serviço | Rotas de API | Páginas Vue |
|---|------|--------|-----------|----------|
| 1 | Autenticação e autorização | AuthController | 3 | LoginPage |
| 2 | Gerenciamento de plataformas | PlatformController | 3 | — |
| 3 | Gerenciamento de contas | AccountController | 5 | AccountList, AccountBind |
| 4 | Planos de anúncios | CampaignController | 6 | CampaignList |
| 5 | Grupos de anúncios | AdGroupController | 5 | AdGroupList |
| 6 | Criativos | CreativeController | 2 | CreativeList |
| 7 | Relatórios de dados | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Monitoramento de alertas | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Central de notificações | NotificationController | 4 | NotificationList |
| 10 | Lance automático | BidRuleController | 5 | BidRuleList |
| 11 | Modelos de segmentação | TargetingTemplateController | 5 | — |
| 12 | Administração do sistema | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Sincronização de dados | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Biblioteca de materiais | AssetController | 4 | AssetGallery |
| 15 | Alerta de orçamento | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Calendário de veiculação | CalendarService | 1 | CampaignCalendar |
| 17 | Atribuição entre plataformas | AttributionEngine | 2 | AttributionReport |
| 18 | Verificação de saúde | HealthController | 2 | — |
| 19 | Captcha | CaptchaController | 2 | — |
| 20 | Documentação da API | DocController | 1 | — |

**Total**: 20 módulos, 65+ rotas, 18 páginas Vue

---

## Módulo 1: Autenticação e autorização

- Verificação de captcha (opcional)
- Consulta a tabela `admin_users`
- Verificação com bcrypt `password_verify()`
- Geração de JWT Token (TTL de 24h)
- Token antigo é adicionado automaticamente à lista negra
- Extrai `uid` do Token para consultar as informações do usuário

Interfaces: login / refresh de Token / usuário atual → [módulo 2 do api.md](api.pt.md#模块-2-认证)

---

## Módulos 2-3: Gerenciamento de plataformas e contas

- Lista de plataformas com cache de 1 hora (Redis), integração com emoji de bandeira do Season
- Fluxo OAuth: gerar state aleatório → construir URL de autorização → processar callback → armazenar Token
- Lista/detalhes de contas com cache de 5 minutos

Interfaces: lista de plataformas / OAuth / CRUD de contas + sincronização → [módulo 3 do api.md](api.pt.md#模块-3-平台--账户)

---

## Módulos 4-6: Hierarquia de veiculação de anúncios

### Estrutura de dados

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- Criação de planos via adaptador da plataforma + gravação local
- Suporta filtros por plataforma/status/palavra-chave, e a lista inclui o resumo de hoje
- A criação de grupos de anúncios suporta `targeting_template_id` para carregar modelos de segmentação

Interfaces: planos / grupos de anúncios / criativos → [módulos 4-6 do api.md](api.pt.md#模块-4-广告计划)

---

## Módulo 7: Relatórios de dados

- Resumo do dashboard com cache de 5 minutos: 8 cartões de indicadores KPI + gráfico de linhas de tendência diária + gráfico de barras por plataforma
- Dimensões de relatórios personalizados: date, platform, campaign
- Métricas: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Formatos de exportação: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (impressão HTML)

Interfaces: resumo / personalizado / exportação → [módulo 7 do api.md](api.pt.md#模块-7-报表)

---

## Módulo 8: Monitoramento de alertas

### Fluxo de avaliação do AlertEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Canais de notificação

| Canal | Status | Implementação |
|------|------|------|
| web | ✅ | Grava em erik_notifications |
| email | Placeholder | Stub echo |
| sms | Placeholder | Stub echo |
| Redis pub/sub | ✅ | Push JSON no canal `alert:new` |

Interfaces: CRUD de regras / registros de alertas / confirmação / não lidos → [módulo 8 do api.md](api.pt.md#模块-8-告警)

---

## Módulo 9: Central de notificações

- Polling de 30s com Pinia store no frontend
- Ícone de sino na barra lateral + badge com número de não lidos

Interfaces: lista / não lidos / marcar como lido / marcar todos como lidos → [módulo 9 do api.md](api.pt.md#模块-9-通知)

---

## Módulo 10: Engine de lance automático

### Fluxo de avaliação do BidEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Campos da regra

| Campo | Tipo | Descrição |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Métrica monitorada |
| condition | gt/gte/lt/lte | Condição de disparo |
| threshold | DECIMAL(12,2) | Limite |
| scope | tenant/platform/campaign | Escopo de aplicação |
| action_type | adjust_budget/toggle_pause/toggle_enable | Ação |
| adjust_step | INT (centavos) | Passo do ajuste de orçamento (positivo=adicionar, negativo=reduzir) |
| budget_min, budget_max | BIGINT | Limites do orçamento |
| cooldown_minutes | INT | Período de resfriamento |

Interfaces: CRUD de regras / histórico de lances → [módulo 10 do api.md](api.pt.md#模块-10-自动出价)

---

## Módulo 11: Modelos de segmentação de público

### Integração com grupos de anúncios

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### JSON Schema genérico

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

Interfaces: CRUD de modelos → [módulo 11 do api.md](api.pt.md#模块-11-定向模板)

---

## Módulo 12: Administração do sistema (Admin)

- IDs da lista de usuários codificados com hashids
- Senhas de novos usuários com hash bcrypt
- Usuários desabilitados usam desabilitação suave (status=0)

Campos do log de auditoria: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

Interfaces: gerenciamento de usuários / logs de auditoria / papéis → [endpoints Admin do api.md](api.pt.md#admin-端点端口-8789)

---

## Módulo 13: Sincronização de dados

### Fluxo do DataSyncTask (a cada 10 minutos)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Formato de resposta

### Sucesso
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Paginação
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Erro
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Módulo 14: Biblioteca de materiais de anúncios

- Tipos suportados: image/jpeg, image/png, image/gif, image/webp, video/mp4
- Armazenamento de arquivos: `public/uploads/assets/`
- Frontend: galeria em grade + upload por arrastar e soltar + prévia de imagens + reprodução de vídeos + copiar URL

Interfaces: upload / lista / detalhes / exclusão → [módulo 12 do api.md](api.pt.md#模块-12-素材库)

---

## Módulo 15: Alerta de orçamento

- Alertas em três níveis: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask executa a cada 15 minutos
- Deduplicação: o mesmo plano no mesmo nível é notificado apenas uma vez por dia
- Grava na tabela `erik_notifications`

Interface: alerta de orçamento → [módulo 7 do api.md](api.pt.md#模块-7-报表)

---

## Módulo 16: Calendário de veiculação

- Agrega os agendamentos de campaigns por data
- Gráfico Gantt no frontend: eixo x datas, eixo y planos, cores distintas por plataforma
- Suporta alternância entre visões mensal/semanal

Interface: calendário de veiculação → [módulo 7 do api.md](api.pt.md#模块-7-报表)

---

## Módulo 17: Atribuição entre plataformas

### Modelos de atribuição

| Modelo | Algoritmo |
|------|------|
| first_touch | Primeiro contato 100% |
| last_touch | Último contato 100% |
| linear | Todos os contatos divididos igualmente (1/N) |
| time_decay | e^(-λ×Δt), meia-vida de 7 dias |
| position_based | Primeiros 40% + últimos 40% + meio 20% |

- Janela de retrospectiva: 30 dias
- Origem dos contatos: `erik_report_metrics` (cliques > 0)
- Resultados gravados em `erik_attribution_results`
- Frontend: AttributionReport.vue com alternância de modelos + cartões de estatísticas + gráfico de barras ECharts + tabela de detalhes

### Tabelas de dados

| Tabela | Campos |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

Interfaces: análise de atribuição / lista de modelos → [módulo 7 do api.md](api.pt.md#模块-7-报表)

### Verificação de saúde
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## Módulo 18: Resiliência de chamadas de plataforma (disjuntor / degradação)

### Máquina de estados do disjuntor

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — estado por plataforma:

| Estado | Gatilho | Comportamento |
|--------|---------|---------------|
| CLOSED | Normal | Chamadas liberadas |
| OPEN | 5 falhas consecutivas | Falha rápida, pula a plataforma |
| HALF_OPEN | Após 30s de resfriamento | Uma chamada de sonda liberada |
| CLOSED | Sonda bem-sucedida | Recuperado, contador zerado |
| OPEN | Nova falha na sonda | Reabre o disjuntor |

### Proxy GuardedAdapter

- `AdapterRegistry::get()` retorna um proxy GuardedAdapter; 14 pontos de chamada sem alteração
- Em OPEN lança `CircuitBreakerOpenException` (falha rápida); a camada de tarefas captura e absorve = degradação pulando a plataforma
- Método Generator: iteração completa → success, interrupção → failure

### Verificação de timeouts

- Os 29 adaptadores incluem CURLOPT_TIMEOUT (30/60s) + CURLOPT_CONNECTTIMEOUT (10s)

### Cobertura de testes

- CircuitBreakerTest 8 casos + GuardedAdapterTest 13 casos

### Limitação conhecida

- Estado em memória de um nó; implantação multinó requer estado compartilhado em Redis
