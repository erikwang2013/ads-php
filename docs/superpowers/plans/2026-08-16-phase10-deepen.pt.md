# Phase 10: Plano de Implementação — Aprofundamento e Comercialização

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** Com base nos contratos das Fases 7-9 e nos canais múltiplos, implementar quatro capacidades de aprofundamento: visualização do status de sincronização, ciclo fechado de dados de conversão, empacotamento CI do mobile e cotas SaaS multitenant.

**Fonte:** Direções inferidas pela auditoria da equipe na Fase 7 (researcher: ES/separacão leitura-escrita/filas, CI de Flutter/HarmonyOS, integração real de 29 plataformas, cotas de cobrança SaaS, ciclo fechado de conversão, visualização do status de sincronização, lances com IA)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## Situação atual (verificada)

| Subitem candidato | Situação atual |
|---|---|
| Visualização do status de sincronização | A tabela `ads_sync_errors` + `RetrySyncTask` (3 tentativas, backoff de 5^n minutos) já existem; **sem página/API de frontend exibindo a taxa de falha e a latência da sincronização** |
| Ciclo fechado de dados de conversão | As tabelas `ads_conversions` + `ads_attribution_results` existem e o mecanismo de atribuição está implementado; **sem ponto de coleta de dados de conversão** (API de retorno/rastreamento) |
| CI do mobile | `ci.yml` apenas PHP syntax→PHPUnit→vue-tsc→Docker; **sem build/empacotamento de Flutter/HarmonyOS** |
| SaaS multitenant | A tabela `ads_tenants` + o middleware TenantIdentify existem; **sem cobrança/cotas/estatísticas de uso** |
| Implementação do ES | scout.php configurado + dependência webman-scout adicionada; **docker-compose sem serviço ES** |
| Integração real de 29 plataformas | Código dos 29 adaptadores completo; **sem registros de integração com sandbox/credenciais** (exige credenciais externas; marcado como item manual) |

## Task 1: Visualização do status de sincronização

### Files:
- Modificar: `service/plugin/ads-api/controller/v1/DashboardController.php` ou criar `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Criar: `admin/public/web/src/views/sync/SyncStatus.vue` (ou incorporar na página de sistema)

### Pontos de design
- Endpoints: `GET /api/sync/status` (por conta: last_sync_at, taxa de sucesso, falhas de hoje, tentativas pendentes) + `GET /api/sync/errors` (lista paginada de erros com last_error/retry_count/next_retry_at)
- Frontend: página de status de sincronização (tabela + cards de resumo), apenas nas linhas de versão Full/Standard
- Fontes de dados: ads_platform_accounts (last_sync_at) + ads_sync_errors

## Task 2: API de coleta de dados de conversão

### Files:
- Modificar: `service/plugin/ads-api/controller/v1/` (adicionar ConversionController + route)
- Create: `service/plugin/ads-report/service/ConversionService.php`

### Pontos de design
- Endpoints: `POST /api/conversions` (o negócio envia conversões: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (consulta)
- Validação: campaign_id existente, valor não negativo, formato de data; gravar em ads_conversions
- Integração com atribuição: o envio pode disparar o recálculo de atribuição (ou documentar que o AttributionEngine existente recalcula por agendamento/manualmente)
- Frontend: adicionar explicação/demonstração de "envio de conversões" na página de relatório de atribuição (opcional)

## Task 3: Empacotamento CI do mobile

### Files:
- Modificar: `.github/workflows/ci.yml` (novo job: Flutter build (web + linux ou apk) + verificação estática do HarmonyOS)

### Pontos de design
- Flutter: `flutter pub get && flutter analyze && flutter build web` (ou apk, escolher o alvo construível conforme o estado do repositório; se o ambiente flutter for limitado, usar dart analyze)
- HarmonyOS: sem toolchain padrão de CI no Linux; documentar a verificação estática ou pular (com anotação)
- Paralelo ao job php-tests existente, sem bloquear o fluxo principal

## Task 4: Cotas SaaS multitenant (MVP)

### Files:
- Modificar: `service/plugin/ads-tenant/` (adicionar QuotaService)
- Modify: `service/plugin/ads-api/config/route.php` + controller

### Pontos de design
- Dados: adicionar campo quota em ads_tenants ou nova tabela ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- Pontos de verificação: número de contas vinculadas, planos criados, sincronizações diárias (verificar nas entradas de AccountController/CampaignController/DataSyncTask)
- Endpoint: `GET /api/tenant/quota` (uso + cotas)
- Frontend: exibir uso de cotas na página de sistema (opcional; o MVP pode ter apenas a API)
- Linhas de versão: valores padrão de quota diferenciados por lite/standard/full (constantes de config)

## Aceitação (por Task)
- [ ] Task 1: endpoints da sync API disponíveis, página de frontend exibindo, cobertura de testes
- [ ] Task 2: API de envio de conversões gravável e consultável, validações ativas, cobertura de testes
- [ ] Task 3: novo job de CI aprovado (ou itens ignorados claramente anotados)
- [ ] Task 4: quota API com retornos corretos, bloqueio de excesso ativo, cobertura de testes
- [ ] Todos: `php vendor/bin/phpunit --no-coverage` passando integralmente, vue-tsc aprovado

## Fora do escopo desta fase (requer recursos externos)
- Integração real de 29 plataformas (requer credenciais/sandbox de cada plataforma)
- Implementação do serviço ES (requer adicionar o serviço ES ao docker-compose e a inicialização de índices)
- Sugestões de lances com IA (preparação de modelo/dados)

