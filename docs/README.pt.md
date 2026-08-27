# Ads Platform — Sistema de gerenciamento de publicidade multiplataforma

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Visão geral

Integra **29 plataformas de publicidade**, gerencia de forma unificada a veiculação de anúncios e os relatórios de dados entre plataformas, com suporte a monitoramento de alertas, lances automáticos e acesso em múltiplos dispositivos.

> Design de arquitetura → [docs/architecture.md](docs/architecture.pt.md)  
> Módulos de funcionalidades → [docs/features.md](docs/features.pt.md)  
> Documentação da API → [docs/api.md](docs/api.pt.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> Comparação de versões → [docs/versions.md](docs/versions.pt.md) (Lite open source / Standard & Full contate erik@erik.xyz)

### Plataformas suportadas

#### Nacionais (16)
| Plataforma | Adaptador | Autenticação |
|------|--------|------|
| Juliang (Ocean Engine) | Juliang | OAuth2 Access-Token |
| Baidu Marketing | Baidu | OAuth2 + assinatura de envelope |
| Taobao/Alimama | Taobao | OAuth2 + MD5 |
| Tencent Ads | Tencent | OAuth2 + nonce |
| Kuaishou Magnet Engine | Kuaishou | OAuth2 parâmetro de URL |
| Xiaohongshu Dandelion | Xiaohongshu | OAuth2 Bearer |
| Weibo Fans Tong | Weibo | OAuth2 Bearer |
| Bilibili Huahuo | Bilibili | OAuth2 Bearer |
| Youku Ads | Youku | OAuth2 + MD5 |
| Meituan Ads | Meituan | OAuth2 Bearer |
| Zhihu Ads | Zhihu | OAuth2 Bearer |
| Qihoo 360 Promo | Qihoo360 | API Key + Sign |
| Sogou Promo | Sogou | API Key + Sign |
| Umeng | Umeng | API Key + MD5 |
| Jingdong Jingzhuntong | Jingdong | OAuth2 + MD5 |
| Pinduoduo Ads | Pinduoduo | OAuth2 + Sign personalizado |

#### Internacionais (13)
| Plataforma | Adaptador | Autenticação |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 parâmetro de URL |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## Stack de tecnologia

| Camada | Tecnologia | Descrição |
|----|------|------|
| Backend | webman v2 + PHP 8.2+ | 7 plugins, 65+ endpoints de API |
| Banco de dados | MySQL 8.0 | 28 tabelas, prefixo erik_, chave primária Snowflake BIGINT |
| Cache | Redis 7 | Cache de três níveis (L1 memória/L2 APCu/L3 Redis), contagem de rate limit, Pub/Sub, fila de mensagens |
| Busca | Elasticsearch | Sincronização automática de índice webman-scout (configurado) |
| Admin | webman-admin v2 + Vue 3 + TypeScript + Element Plus | Backend PHP (porta 8789), SPA conecta direto à API de negócio (porta 8788), 19 páginas, visualização ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | Responsivo PC/Mobile, layout Desktop Shell, 12 páginas |
| HarmonyOS | ArkTS + ArkUI | 6 páginas implementadas, cliente HTTP pronto |
| Implantação | Docker + Nginx + GHCR | Docker Compose com um clique, GitHub Actions compila e publica automaticamente |

## Diagrama de arquitetura

![Diagrama de arquitetura do sistema](docs/diagrams/svg/architecture.pt.svg)

### Diagrama de fluxo de requisições

![Diagrama de fluxo de requisições](docs/diagrams/svg/request-flow.pt.svg)

### Diagrama de módulos funcionais

![Diagrama de módulos funcionais](docs/diagrams/svg/functional-modules.pt.svg)

### Diagrama do ciclo de vida dos dados

![Diagrama do ciclo de vida dos dados](docs/diagrams/svg/data-lifecycle.pt.svg)

> A versão completa contém todas as anotações de detalhes, o pipeline do Admin, o gráfico de Gantt das tarefas agendadas e a máquina de estados do cache → [docs/diagrams/](docs/diagrams/) |

> Explicações detalhadas da arquitetura, arquitetura de segurança e design de alta concorrência em [Documento de design de arquitetura](docs/architecture.pt.md) | Especificação de design histórica em [design.md](docs/superpowers/specs/design.pt.md)

## Explicação da arquitetura

- **`service/`** — serviço de API de negócio do usuário webman v2, escutando na porta **8788**. Trata a integração com plataformas de publicidade, autorização OAuth, sincronização de dados, engine de relatórios, monitoramento de alertas e outras lógicas de negócio.
- **`admin/`** — painel administrativo independente webman-admin v2, escutando na porta **8789**. Inclui backend PHP (autenticação, gerenciamento de usuários, configuração do sistema) e frontend Vue 3 SPA.
- **Comunicação entre o painel administrativo e o serviço de negócio** — a Vue SPA conecta diretamente à API do service via axios (baseURL `/api`); as rotas exclusivas do admin (`/api/admin/*`) são servidas pelo backend PHP do admin (8789), com o Nginx fazendo o roteamento por caminho.
- **Modo de desenvolvimento** — o Vite dev server (porta 5173) faz proxy de `/api` para service:8788; o backend PHP do admin fornece autenticação de sessão e serviço estático da SPA na porta 8789.
- **Modo de produção** — o Nginx roteia `/` para admin:8789 (SPA do painel administrativo) e `/api/` para service:8788 (API de negócio).

## Integração Erik Stack

| Pacote | Finalidade |
|----|------|
| `erikwang2013/snowflake-php` | Geração de ID Snowflake distribuído |
| `erikwang2013/hashids` | Criptografia/descriptografia de parâmetros de ID da API |
| `erikwang2013/jwt-webman` | Token de autenticação JWT |
| `erikwang2013/encryption` | Criptografia de dados sensíveis na camada de API |
| `erikwang2013/encryptable` | Criptografia automática em nível de campo do DB |
| `erikwang2013/webman-scout` | Sincronização de dados Elasticsearch |
| `erikwang2013/season` | Bandeiras de países |
| `erikwang2013/poster-php` | Captcha deslizante (proteção de login) |
| `hg/apidoc` | Geração automática de documentação da API (anotações + Web UI) |

## Internacionalização

Todas as interfaces suportam alternância bilíngue **Chinês (zh-CN)** / **Inglês (en)**:

| Plataforma | Tecnologia | Método de alternância |
|----|------|---------|
| Admin | vue-i18n v9 | Menu suspenso de idioma na TopBar, persistência em localStorage |
| Service API | `erik\support\I18n` | Cabeçalho Accept-Language / parâmetro `?lang=` |
| Flutter | AppLocalizations + Delegate | Detecção automática do idioma do sistema |
| HarmonyOS | StringResources | Alternância via `setLang()` |

## Segurança

### Lado Service (14 camadas globais + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware (camada de rotas)

### Lado Admin (10 camadas globais + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck (camada de rotas)

### Visão geral das proteções (22 itens)

| Categoria | Item de proteção | Descrição |
|------|--------|------|
| Detecção de entrada | XSS (11 padrões) | script/iframe/event handler/javascript:/data: |
| | Path traversal (7 padrões) | ../ / null byte / /etc/passwd / .env / .git |
| | Injeção de Header | Detecção de CRLF |
| | Limite de tamanho do body | 10 MiB |
| | Lista de permissões Content-Type | JSON/Form/Multipart/Plain |
| | Injeção de SQL | Detecção de padrões UNION/DROP/ALTER |
| Autenticação | Vínculo do JWT Token | Verificação de hash IP + User-Agent |
| | Refresh de Token + lista negra | Token antigo expira automaticamente |
| | Throttle de login | 5 falhas → bloqueio de 15 minutos (Redis) |
| | Limite de sessões concorrentes | Máximo de 3 Tokens ativos por usuário |
| | Captcha | Captcha deslizante (válido por 5 minutos, tolerância de 5px) |
| Validação de requisição | Lista de permissões CORS | Lista de permissões de domínios em produção |
| | Validação Origin/Referer | Verificação de origem entre domínios |
| | CSRF Token | Verificação de token de sessão no Admin |
| | Anti-replay | Nonce + Timestamp ±5min (fora do navegador) |
| | Rate limit de API | Janela deslizante 60 req/60s |
| | Proteção SSRF | Lista de permissões de redirect_uri do OAuth |
| Cabeçalhos de resposta | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Anti clickjacking + HTTPS forçado |
| | X-Content-Type-Options | nosniff |
| Proteção de dados | Criptografia de transporte | EncryptionMiddleware (X-Encrypted) |
| | Criptografia de armazenamento | Encryptable (nível de campo do DB) |
| | Mascaramento de logs | password/token/secret → \*\*\* |

### Diagrama de arquitetura de segurança

![Diagrama de arquitetura de segurança](docs/diagrams/svg/security.pt.svg)

**Defesa em profundidade**: camada externa (Nginx) → guardas de entrada (5 camadas de middleware) → autenticação de identidade (7 itens) → validação de entrada (4 itens) → controle de frequência → criptografia de dados → auditoria e rastreabilidade

**Autenticação**: o service e o admin usam uniformemente a tabela `admin_users` + hash bcrypt, JWT 24h + rotação de refresh

**Auditoria**: todas as operações registram IP / User-Agent / Client-Platform / detalhes da operação

**Confirmação secundária**: operações de exclusão/desvinculação/em lote usam o modo "digitar palavra de confirmação" (`GlobalConfirm` + `useConfirmStore`)

---

## Funcionalidades avançadas

| Funcionalidade | Descrição | Tecnologia |
|------|------|------|
| Biblioteca de materiais | Gerenciamento de upload de imagens/vídeos, prévia em galeria, copiar URL | AssetController + galeria Vue |
| Alerta de orçamento | Rastreamento em tempo real do consumo do orçamento diário, alertas em três níveis (50/80/100%) | BudgetAlertService + Cron de 15min |
| Calendário de veiculação | Gráfico Gantt entre plataformas, visões mensal/semanal, cores por plataforma | CalendarService + Gantt Vue |
| Atribuição entre plataformas | Atribuição com 5 modelos (first/last/linear/time_decay/position_based), retrospectiva de 30 dias | AttributionEngine + ECharts |

---

## Alta concorrência

| Otimização | Solução | Arquivo |
|------|------|------|
| Separação de leitura/escrita do banco | Banco principal `shared` + réplica somente leitura `read_replica`, SELECT roteado automaticamente para a réplica | `config/database.php` |
| Pool de conexões do DB | Conexão persistente `PDO::ATTR_PERSISTENT` + pré-aquecimento com inicialização de fuso horário | `config/database.php` |
| Pool de conexões Redis | Conexão persistente `persistent` + configuração `readonly` de separação leitura/escrita | `config/redis.php` |
| Cache de três níveis | L1 memória do processo → L2 memória compartilhada APCu → L3 Redis | `support/CacheService.php` |
| Fila de mensagens assíncrona | Redis List com 4 canais (sync/report/export/notification) | `support/AsyncJobService.php` |
| Rate limit em níveis no Nginx | 30r/s + burst 20 + 20 conexões concorrentes + keepalive 32 | `docker/nginx/admin.conf` |
| Escala horizontal | upstream com múltiplas instâncias + failover + sticky session | `docker/nginx/admin.conf` |
| Aceleração CDN | Recursos estáticos `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Início rápido

### Instalação Web com um clique (recomendado)

Após iniciar o serviço, acesse `/install` no navegador para entrar no assistente de instalação:

```bash
# 启动管理后台 (端口 8789)
cd admin && composer install && php start.php start

# 打开浏览器访问 http://localhost:8789/install
# 在安装向导中填写数据库信息、管理员账户，点击「开始安装」
```

O assistente de instalação o guiará na página:
1. **Conexão com o banco de dados** — preencha o host MySQL, porta, nome do banco, usuário e senha, com suporte a teste de conexão
2. **Configuração Redis** — preencha as informações de conexão do Redis (opcional)
3. **Conta de administrador** — defina usuário, senha e nome de exibição do login do painel
4. **Instalação com um clique** — cria o banco automaticamente, executa o `install.sql` para criar 28 tabelas e inserir dados de seed, atualiza a senha do administrador

Após a instalação, acesse `/` para entrar no painel administrativo e faça login com o usuário e a senha definidos.

### Docker (recomendado para produção)

```bash
# 启动全部服务 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 初始化数据库（创建表 + 种子数据）
make db-init

# 访问
# 管理后台: http://localhost
# 安装向导: http://localhost/install
# API: http://localhost/api（Header: X-API-Version: v1）
```

### Desenvolvimento local

```bash
# 服务端 (端口 8788)
cd service && composer install && php start.php start

# 管理后台 (端口 5173)
cd admin/public/web && npm install && npm run dev

# Flutter App
cd apps/flutter && flutter run -d chrome  # Web PC
# HarmonyOS App
# 使用 DevEco Studio 打开 apps/harmonyos 目录
cd apps/flutter && flutter run -d android # Mobile

# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误
```

---

## Estrutura do projeto

```
ads-php/
├── service/                           # 用户端业务服务 (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 端点，版本路由)
│   │   │   ├── controller/v1/         # 17 个控制器
│   │   │   ├── middleware/            # 15 个中间件
│   │   │   ├── config/route.php       # 路由定义
│   │   │   └── route_helpers.php      # versioned() 辅助函数
│   │   ├── ads-platform/              # 平台适配器核心
│   │   │   ├── adapter/               # 29 个平台适配器
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL 迁移 + 性能索引
│   │   ├── ads-account/               # OAuth 账户管理
│   │   ├── ads-task/                  # 定时任务调度 (6 cron)
│   │   ├── ads-alert/                 # 告警监控引擎 + 预算预警
│   │   ├── ads-report/                # 报表引擎 (CSV/Excel/PDF) + 归因引擎 + 投放日历
│   │   └── ads-tenant/                # 多租户管理
│   ├── support/                       # Erik Stack 工具类
│   │   ├── ControllerTrait.php        # 控制器公共 trait
│   │   ├── JwtService.php             # JWT 包装类
│   │   ├── CacheService.php           # Redis 缓存服务
│   │   ├── ExceptionHandler.php       # API 异常处理器
│   │   └── ApiResponse.php            # 统一响应格式
│   ├── config/                        # 全局配置 (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit 测试 (244 tests)
│   │   ├── Unit/                      # 单元测试 (Middleware, Task)
│   │   └── Integration/               # 集成测试 (Auth, Health)
│   └── start.php                      # 服务入口
├── admin/                             # 独立管理后台 (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 个 Vue 页面
│   │   │   ├── dashboard/             # 仪表盘 (ECharts)
│   │   │   ├── campaign/              # 广告计划
│   │   │   ├── adgroup/               # 广告组
│   │   │   ├── creative/              # 广告创意
│   │   │   ├── report/                # 报表分析 + 导出
│   │   │   ├── alert/                 # 告警规则 + 记录
│   │   │   ├── notification/          # 通知中心
│   │   │   ├── bid/                   # 自动出价规则
│   │   │   └── system/                # 用户管理 + 审计日志
│   │   ├── api/                       # 9 个 API 客户端
│   │   ├── stores/                    # 4 个 Pinia Store
│   │   └── components/                # 共享组件 (ListPageLayout 等)
│   ├── app/                           # PHP 后端 (controller/middleware)
│   └── config/                        # Admin 配置
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12 个功能页面 + Shell 布局
│   │       ├── config/menu_config.dart # 两级菜单配置
│   │       ├── router.dart            # GoRouter (ShellRoute + 路由守卫)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client 就绪)
├── docker/                            # Docker & Nginx 配置
├── .github/workflows/                 # CI (语法→测试→TS→Docker) + CD (构建推送)
├── docs/                              # 设计文档、实施计划、Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## Endpoints da API

> Todas as definições dos endpoints da API estão em [docs/api.md](docs/api.pt.md) (incluindo exemplos de requisição/resposta, códigos de erro e políticas de rate limit).
> Documentação online hg/apidoc: após iniciar o serviço, acesse `http://127.0.0.1:8788/apidoc`

## Banco de dados

**Convenção de nomenclatura**: prefixo de tabela `erik_`, chave primária `BIGINT UNSIGNED PRIMARY KEY` (sem autoincremento, Snowflake ID), engine InnoDB, charset utf8mb4

| Categoria | Nome da tabela | Finalidade |
|------|------|------|
| Base | `erik_tenants` | Multitenant |
| Contas | `erik_platform_accounts`, `erik_auth_tokens` | Contas de plataforma OAuth |
| Veiculação | `erik_campaigns`, `erik_ad_groups`, `erik_creatives` | Hierarquia de veiculação de anúncios |
| Relatórios | `erik_report_metrics`, `erik_report_extras` | Métricas unificadas de relatórios |
| Materiais | `erik_assets` | Biblioteca de materiais criativos |
| Segmentação | `erik_targeting_templates` | Modelos de segmentação de público |
| Atribuição | `erik_conversions`, `erik_attribution_results` | Rastreamento de conversões + resultados de atribuição |
| Lances | `erik_bid_rules`, `erik_bid_logs` | Regras de lance automático + histórico |
| Alertas | `erik_alert_rules`, `erik_alert_logs` | Monitoramento de alertas |
| Notificações | `erik_notifications` | Notificações no sistema |
| Sistema | `erik_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Erros de sincronização, RBAC, auditoria |

---

## Tarefas agendadas

| Tarefa | Frequência | Função |
|------|------|------|
| TokenRefreshTask | A cada 55 minutos | Escaneia Tokens OAuth expirados e atualiza automaticamente |
| DataSyncTask | A cada 10 minutos | Busca planos + grupos de anúncios + criativos + relatórios de cada plataforma, grava em tabelas unificadas e limpa o cache |
| AlertCheckTask | A cada 5 minutos | Percorre as regras de alerta habilitadas, avalia limites e dispara notificações |
| BidCheckTask | A cada 10 minutos | Percorre as regras de lance automático, consulta métricas e executa ajustes de orçamento/início-pausa |
| BudgetCheckTask | A cada 15 minutos | Percorre os planos em veiculação, rastreia o consumo do orçamento diário com alertas em três níveis (50/80/100%) |
| RetrySyncTask | A cada 3 minutos | Repete tarefas de sincronização com falha (máximo de 3 vezes, backoff exponencial) |

---

## Testes

```bash
cd service && ./vendor/bin/phpunit
# 244 测试 / 654 断言
```

**Cobertura**: middlewares (Version/SQLGuard/SecurityHeaders) · objetos de dados (CampaignData/FieldMapping/Hashids) · engines (ReportBuilder/AdapterRegistry) · testes de integração (Auth/Health)

```bash
# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误

# Dart 分析
cd apps/flutter && dart analyze   # 零错误
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): pipeline automático — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): acionamento manual — **Docker Buildx → push para GHCR (service/admin/admin-php) → notificação de implantação**

`.github/dependabot.yml` atualiza automaticamente as dependências Composer + npm + Docker toda semana.

---

## Skills

`docs/skills/` — 11 habilidades de projeto reutilizáveis:

| Skill | Descrição |
|------|------|
| `adapter-generator` | Gera novos adaptadores de plataforma de publicidade (modelo de 14 métodos) |
| `migration-generator` | Gera arquivos de migração SQL (prefixo erik_ + BIGINT PK) |
| `erik-stack` | Guia de integração dos 8 pacotes do Erik Stack |
| `admin-page-generator` | Gera páginas do painel administrativo Vue3 |
| `api-endpoint` | Adiciona endpoints RESTful de API |
| `tdd-workflow` | Fluxo de validação TDD (teste→implementação→sintaxe→TypeScript→commit) |
| `security-middleware` | Adiciona camada de middleware de segurança (especificação da interface + registro + referência da cadeia existente) |
| `version-split` | Divisão nas três versões Lite/Standard/Full (etapas + atualização de configuração) |
| `cache-strategy` | Estratégia de cache de três níveis (L1 memória/L2 APCu/L3 Redis + recomendações de TTL) |
| `attribution-setup` | Engine de atribuição entre plataformas (5 modelos + chamadas de API + preparação de dados) |
| `high-concurrency` | 8 otimizações de alta concorrência (separação leitura/escrita/pool de conexões/fila de mensagens/escala horizontal/CDN) |


## Open source não é fácil, apoie-nos

| WeChat | Alipay |
|:---:|:---:|
| ![WeChat](./docs/weixinpay.png "WeChat") | ![Alipay](./docs/alipay.png "Alipay") |

### Doação por transferência global (Global Transfer Donation)

**Informações do beneficiário (Beneficiary)**

| Campo | Valor |
|------|-----|
| Nome do beneficiário (Name) | WANG KEXUN |
| Número da conta do beneficiário (Account No.) | 881015918251 |

**Banco receptor (Receiving Bank) — ZA Bank**

| Campo | Valor |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| Nome do banco (Bank Name) | ZA Bank Limited |
| Código do banco (Bank Code) | 387 |
| Endereço do banco (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Banco correspondente para remessas transfronteiriças (se necessário, Correspondent Bank)**: estas são informações do banco intermediário (agente), não do banco receptor; consulte o banco remetente para saber se é necessário fornecê-las.
>
> - **Dólar de Hong Kong, RMB e dólar americano**: Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · código do banco 006 · Hong Kong Branch (código da filial 391) · Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **Outras moedas**: THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

---

## Licença

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.

