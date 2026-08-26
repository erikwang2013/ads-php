# Phase 9: Plano de Implementação da Integração Real do HarmonyOS

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** Trocar as 6 páginas do HarmonyOS de dados simulados para chamadas reais de API (service :8788), corrigir o baseUrl codificado do ApiClient, tornar o login real e fazer do HarmonyOS um terceiro cliente utilizável.

**Fonte:** Auditoria da equipe na Fase 7 (inventário do mobile-dev: as 6 páginas do HarmonyOS usam apenas dados simulados, 0 chamadas reais, baseUrl `http://127.0.0.1:8788/api` codificado no ApiClient)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## Situação atual (verificada)

| Componente | Status |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login completos; baseUrl `http://127.0.0.1:8788/api` codificado (o Flutter usa o caminho relativo de mesma origem `/api`); login() sem chamadores |
| `pages/LoginPage.ets` | Login simulado (setTimeout de 1s para redirecionar), comentário "replace with actual API call" |
| `pages/DashboardPage.ets` | Métricas codificadas em `@State` (totalCost=1250000 etc.) |
| `pages/CampaignListPage.ets` | L187 placeholder de comentário `/campaigns` |
| `pages/AccountPage.ets` | L138 placeholder de comentário `/accounts` |
| `pages/AlertPage.ets` | L146 placeholder de comentário `/alerts` |
| `pages/ReportPage.ets` | L242 placeholder de comentário `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric já existem |
| i18n | StringResources.ets（15+ keys） |

## Task 1: Aprimoramento do ApiClient

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Pontos de design
- **baseUrl configurável:** manter setBaseUrl, com o valor padrão ainda `http://127.0.0.1:8788/api` (dispositivos reais/emuladores precisam apontar para um endereço de LAN, conforme comentário); evitar caminho relativo de mesma origem como no Flutter (ArkTS exige URL absoluta)
- **Corrigir o bug de replayHeaders duplicado:** `{ ...this.replayHeaders(), ...this.replayHeaders() }` espalhado duas vezes (no método get) → uma única vez
- **Adaptação do retorno de login():** o service `POST /api/auth/login` retorna `{access_token, token_type, expires_in, user}` (comparar com os campos reais de `service/plugin/ads-api/controller/v1/AuthController.php` — é access_token, não token; corrigir a verificação `data.token` após a confirmação)
- **Tratamento de erros:** lançar erro/retornar mensagem clara quando resp.responseCode não for 2xx; proteção contra falha de JSON.parse
- Manter a convenção existente de get/post/put/delete retornarem `data.data` (desempacotamento do ApiResponse)

## Task 2: Login real no LoginPage

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Pontos de design
- `handleLogin()` chama `ApiClient.login(username, password)`; sucesso → setToken + redirecionar para o Dashboard; falha → toast com a mensagem de erro
- O estado de carregamento isLoading já existe; reutilizar
- Mensagens de erro: preferir a message retornada pelo service (envelope ApiResponse); caso contrário, texto genérico

## Task 3: Tornar reais as cinco páginas de negócio

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`、`CampaignListPage.ets`、`AccountPage.ets`、`AlertPage.ets`、`ReportPage.ets`

### Correspondência de endpoints (confirmada pela auditoria da Fase 7, idêntica ao Flutter após a correção)
| Página | Chamada | Análise |
|---|---|---|
| DashboardPage | `GET /reports/summary` (intervalo de hoje) | `data.overview` → totalCost/total_impressions/avg_ctr etc. (valores em centavos; formatFen já existe) |
| CampaignListPage | `GET /campaigns` | `data.list` (paginado) → modelo Campaign |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → campos do AlertLog (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom`（date_start/date_end/dimensions[]/metrics[]） | `data.list` → ReportMetric |

### Pontos de design
- O carregamento da página (aboutToAppear) dispara a requisição; inicializar os dados @State como vazios/0 para evitar valores simulados residuais
- Em falha de carregamento, exibir erro + tentar novamente (referência ao padrão de erro/nova tentativa das páginas Flutter)
- Unidade monetária: o service retorna números em centavos; formatFen já trata a conversão
- **Sem novos arquivos**, manter a estrutura de UI e o i18n existentes das páginas

## Task 4: Validação

### Aceitação
- [ ] ApiClient sem replayHeaders duplicado; campos de retorno do login consistentes com o AuthController
- [ ] Nenhum dado de negócio simulado codificado restante nas 6 páginas (verificação com grep)
- [ ] Caminhos de chamada das 5 páginas de negócio correspondem um a um às rotas do service (conferir com `service/plugin/ads-api/config/route.php`)
- [ ] Verificação de sintaxe ArkTS (executar se o ambiente tiver o toolchain hvigor/DevEco; caso contrário, documentar e conferir manualmente)
- [ ] Regressão: PHPUnit do service sem impacto

