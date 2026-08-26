# Phase 7: Plano de Implementação da Correção de Contratos entre Plataformas

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Atualização de status (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ concluídas; verificação de regressão do tester aprovada (35 testes OK, verificação cruzada de contratos sem endpoints fantasmas; Fase 7 pronta para aceitação).

**Goal:** Corrigir os problemas de contrato de API entre plataformas encontrados na auditoria da equipe: 3 endpoints fantasmas do Flutter (404), bug de prefixo duplicado no `admin.ts` do Admin, `/system/info` sem rota, ServiceProxy não conectado e documentação desatualizada. Restaurar o consumo consistente da API do service pelos três clientes (Admin/Flutter/HarmonyOS).

**Fonte:** Auditoria paralela da equipe em 2026-08-07 (backend-dev inventariou 61 endpoints de rotas, vue-dev inventariou 50 pontos de chamada do Admin, mobile-dev inventariou o mobile e o researcher fez a comparação cruzada entre o implementado e o planejado)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Corrigir os endpoints fantasmas do Flutter (🔴 prioridade máxima)

### Contexto
Três páginas do Flutter chamam rotas que não existem no service, todas retornando 404:

| Chamada do Flutter | Rota real no service | Solução |
|---|---|---|
| `GET /dashboard` | Nenhuma (resumo do dashboard em `/reports/summary`) | Alterar para `GET /reports/summary` |
| `GET /alerts` | Nenhuma (alertas em `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | Alterar para `GET /alerts/logs` (semântica de lista de alertas) |
| `GET /reports` | Nenhuma (relatórios em `/reports/summary`, `/reports/custom`) | Alterar para `GET /reports/custom` (com parâmetros de data/dimensão/métrica, correspondente ao ReportBuilder::buildCustom) |

### Files:
- Modificar: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` em ×2 pontos, adaptando a estrutura de resposta `data.overview`/`by_platform`/`daily`) ✅
- Modificar: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, adaptando a estrutura paginada `data.list`, campos do AlertLog rule_name/metric/current_value/condition/threshold) ✅
- Modificar: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, parâmetros date_start/date_end/dimensions[]/metrics[], analisando `data.list`, campo cost) ✅
- Verificar: os campos de resposta são consistentes com o retorno real de `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### Aceitação
- [x] As três alterações de caminho concluídas, parâmetros de consulta preservados (parâmetros de data da página de relatório → date_start/date_end + dimensions/metrics) ✅
- [x] Análise de resposta alinhada à estrutura JSON real do backend (overview / paginated list / custom list) ✅
- [x] `flutter analyze` sem erros após as alterações — o cache somente leitura do Flutter SDK neste ambiente impede a execução; usando o `dart analyze` embutido no SDK em todo o projeto, **0 erros** (os 15 avisos existentes já estavam presentes antes das alterações; nenhum problema novo foi introduzido) ✅

---

## Task 2: Corrigir o bug de prefixo duplicado no `admin.ts` do Admin

### Contexto
- O caminho em `admin/public/web/src/api/admin.ts` está escrito como `/api/admin/...`, mas o baseURL do axios já é `/api` (`src/api/index.ts`), resultando em `/api/api/admin/...`; as 5 chamadas de UserManage.vue / AuditLog.vue provavelmente retornam 404.
- **Problema arquitetural profundo (confirmado no relatório final do vue-dev):** o backend do admin (8789) fornece 12 rotas locais (`/api/admin/login`, `me`, `logout`, CRUD de `users`, `roles`, `audit-logs`, `/api/install/*`), mas:
  - o `location /api/` do `docker/nginx/admin.conf` faz proxy_pass de **tudo** para `service_api` (php:8788);
  - o `upstream admin_backend` (admin-php:8789) está definido, mas **nenhum location o referencia** → em produção, `/api/admin/*` nunca chega à porta 8789;
  - o proxy de desenvolvimento do Vite também aponta todo o `/api` para a porta 8788.
  - Conclusão: mesmo corrigindo o prefixo duplicado, `/api/admin/*` continua retornando 404 — as rotas locais do backend do admin não estão conectadas no fluxo de produção.

### Ponto de decisão (requer confirmação de backend-dev + vue-dev + devops)
- Opção A (recomendada): o vue-dev altera os caminhos de `admin.ts` para `/admin/users`, `/admin/audit-logs`, e o **devops adiciona `location /api/admin/` → `proxy_pass http://admin_backend` no Nginx** (antes de `location /api/`, pois prefixo exato tem prioridade de correspondência), para que as rotas exclusivas do admin sejam servidas diretamente pela 8789 e as rotas de negócio continuem na 8788
- Opção B: o backend-dev adiciona rotas `/api/admin/*` no service (sobreposição de responsabilidades com o Admin, não recomendada)
- Opção C: as consultas de negócio também passam pelo ServiceProxy (requer conexão; é a maior mudança; considerar apenas se o Admin precisar de autenticação unificada)

### Files:
- Modificar: `admin/public/web/src/api/admin.ts` (remover o prefixo `/api`)
- Modificar: `docker/nginx/admin.conf` (adicionar `location /api/admin/` → upstream admin_backend)
- Modificar: `admin/public/web/vite.config.ts` (adicionar a regra `/api/admin` → 8789 no proxy de dev, antes de `/api`)
- Verificar: as rotas do backend do admin em `admin/config/route.php` (/api/admin/users etc.) correspondem às chamadas do frontend

### Aceitação
- [x] Caminhos de requisição do frontend consistentes com as rotas reais do backend (sem 404) — os 9 métodos do admin.ts conferidos com o route.php ✅, vue-tsc aprovado
- [x] Nginx / Vite roteiam corretamente `/api/admin/*` para a 8789 e o restante de `/api/*` para a 8788 — Nginx com novo `location /api/admin/`, Vite com novo proxy `/api/admin` (antes de `/api`) ✅
- [x] Funcionalidade das páginas UserManage / AuditLog operacional — caminhos alinhados (incluindo a decisão listRoles → `/admin/users/roles`) ✅

---

## Task 3: `/system/info` sem rota + decisão sobre o ServiceProxy

### Contexto
- `SystemInfo.vue` / `stores/admin.ts` chamam `GET /api/system/info`, rota inexistente no service (apenas /health, /ping); o 404 é engolido pelo try/catch
- `admin/app/controller/ServiceProxy.php` está definido, mas não há nenhum chamador ativo no repositório ("definido mas não conectado")

### Ponto de decisão
- `/system/info`: opção A — o frontend passa a chamar `/health` (já existente no service); opção B — o backend-dev adiciona o endpoint `/api/system/info` no service (retorna informações de versão/ambiente, útil também para HarmonyOS/Flutter; recomendada)
- ServiceProxy: opção A — conectar às APIs exclusivas do admin necessárias (ex.: encaminhamento de logs de auditoria); opção B — excluir a classe e atualizar a documentação declarando "Admin conecta diretamente ao service" (arquitetura atual)

### Executado (2026-08-16)
- **`/system/info` → opção A (frontend passa a chamar `/health`):** SystemInfo.vue usa axios nativo para `GET /health`, verificando `checks.database === 'ok'`; a rota `/health` no service não tem o prefixo `/api`, o Vite já ganhou o proxy `/health` e o Nginx já tinha `location /health`; o código morto em `stores/admin.ts` também foi alterado para `/health` ✅
- **ServiceProxy → opção B (manter + documentar):** a classe é mantida como infraestrutura reservada (`ServiceProxy::init()` é uma auto-inicialização inofensiva), e o comentário em `admin/config/app.php` foi atualizado para "infraestrutura reservada, sem chamadores ativos no momento" ✅

### Aceitação
- [x] Decisão de `/system/info` implementada: frontend sem a chamada (usando /health), sem requisições fantasmas 404 ✅
- [x] Decisão do ServiceProxy implementada: classe mantida, e o comentário no config explica a situação atual ✅

---

## Task 4: Atualização da documentação e uniformização das informações

### Contexto
- O README "14 controladores / 45+ endpoints" está desatualizado (na verdade são 17 controladores / 61 endpoints)
- As checkboxes das fases em `docs/superpowers/plans/` não foram preenchidas (código implementado, mas documentação não marcada)
- O status do HarmonyOS "UI em planejamento" está desatualizado (na verdade, 6 páginas + ApiClient prontos)
- O padrão `.../api/v1` do install.html / InstallController é inconsistente com o padrão `/api` do config (cabeçalho X-API-Version)
- O comentário do CacheService menciona cache de dois níveis, mas são três (L1 memória / APCu / Redis)

### Files:
- Modificar: `README.md` / `README.en.md` (número de controladores, endpoints, status do HarmonyOS, níveis de cache)
- Modificar: `admin/public/install.html` / `admin/app/controller/InstallController.php` (uniformizar o prefixo de versão)
- Modificar: `service/support/CacheService.php` (correção de comentário)
- Opcional: preencher as checkboxes de `docs/superpowers/plans/*.md`

### Executado (2026-08-16)
- README.md / README.en.md: 17 controladores / 61 endpoints / 6 páginas HarmonyOS / 19 páginas Vue / conexão direta SPA — tudo atualizado ✅
- install.html / InstallController: valor padrão `/api/v1` → `/api` (mecanismo do cabeçalho X-API-Version) ✅
- As checkboxes de 8 planos de fase preenchidas ✅ (exceto a fase 7, pendente)

### Aceitação
- [x] Dados do README consistentes com o código (17 controladores / 61 endpoints / 6 páginas HarmonyOS) ✅
- [x] Prefixo de versão do assistente de instalação consistente com o mecanismo X-API-Version ✅

---

## Planejamento das próximas fases (Fases 8-10, fora deste plano)

| Phase | Conteúdo | Status |
|---|---|---|
| Phase 8 | Canais múltiplos de alertas: ads-alert ganha channel/ (Email SMTP, Webhook, placeholder de gateway SMS) — preenche lacunas restantes da Fase 5 | Não iniciada |
| Phase 9 | Integração real do HarmonyOS: 6 páginas conectadas ao ApiClient (atualmente 0 chamadas reais, todos os dados simulados) | Não iniciada |
| Phase 10 | Aprofundamento e comercialização: integração real de 29 plataformas, visualização do status de sincronização, ciclo fechado de dados de conversão, empacotamento CI de Flutter/HarmonyOS, cotas SaaS multitenant | Não iniciada |

