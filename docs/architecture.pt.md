# Documento de design de arquitetura

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. Visão geral do sistema

Sistema de gerenciamento de publicidade multiplataforma, integrando **29 plataformas de publicidade**, cobrindo gerenciamento de veiculação, relatórios entre plataformas, monitoramento de alertas, lances automáticos e segmentação de público. Suporta três modos: SaaS multitenant, operação terceirizada e uso próprio.

---

## 2. Arquitetura de implantação

```
                         ┌──────────────────────────┐
                         │  客户端                   │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 插件         │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 张表  │      │ 缓存/队列│      │ 搜索索引  │
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. Pipeline de processamento de requisições

### 3.1 Lado Service (15 camadas de middleware)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
  → RateLimitMiddleware       (Redis 滑动窗口 60次/60s)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟锁定)
  → SessionLimitMiddleware    (并发会话限制 最大3个活跃Token)
  → SqlGuardMiddleware        (SQL 注入模式检测)
  → ValidationMiddleware      (输入 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time 头 + 慢请求日志)
  → EncryptionMiddleware      (X-Encrypted 请求解密/响应加密)
  → AuthMiddleware            (JWT Bearer Token + IP/UA 绑定)
  → Controller
```

### 3.2 Lado Admin (6 camadas de middleware)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. Estrutura de diretórios

```
ads-php/
├── service/                               # 业务 API 服务 :8788
│   ├── config/                            # 全局配置
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line 双模式)
│   │   ├── middleware.php                 # 11 层全局中间件
│   │   ├── exception.php                  # API 异常处理器
│   │   └── scout.php                      # ES 配置
│   ├── support/                           # 共享工具类 (erik\support)
│   │   ├── ApiResponse.php                # 统一 JSON 响应
│   │   ├── ControllerTrait.php            # 控制器公共 trait
│   │   ├── JwtService.php                 # JWT 包装 (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis 缓存
│   │   ├── HashidsService.php             # ID 加解密
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 异常渲染
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 层
│   │   │   ├── controller/v1/             # 14 个控制器
│   │   │   ├── middleware/                # 7 个中间件
│   │   │   ├── config/route.php           # 45+ 路由
│   │   ├── ads-platform/                  # 平台适配器核心
│   │   │   ├── adapter/                   # 29 个平台适配器
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + 性能索引
│   │   ├── ads-account/                   # OAuth 账户 + 平台账户
│   │   ├── ads-task/                      # 5 个 cron 任务
│   │   ├── ads-alert/                     # 告警引擎 + 通知
│   │   ├── ads-report/                    # 报表引擎 (CSV/Excel/PDF)
│   │   ├── ads-tenant/                    # 多租户
│   │   └── ads-storage/                   # Abstração de armazenamento (local/OSS/COS/S3) + provedores CDN
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # 中间件测试
│   │   ├── Unit/Task/                     # 任务测试 (规划)
│   │   └── Integration/                   # 控制器集成测试
│   └── start.php                          # 入口
├── admin/                                 # 管理后台 :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 页面 (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 个 API 客户端
│   │       ├── stores/                    # 4 个 Pinia Store
│   │       └── components/                # ListPageLayout 等共享组件
│   └── config/                            # Admin 配置
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 功能页面 + Shell 布局
│   │       ├── config/menu_config.dart    # 两级菜单 + 面包屑
│   │       ├── router.dart                # GoRouter + ShellRoute + 路由守卫
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + 平台检测
│   └── harmonyos/                         # HarmonyOS (API Client 就绪)
├── docker/                                # Nginx 配置 + Dockerfiles
├── .github/workflows/                     # CI (语法→测试→TS→Docker) + CD (构建推送)
└── docs/                                  # 设计文档
```

---

## 5. Modelo de dados

### 5.1 Classificação das tabelas

| Categoria | Nome da tabela | Chave primária | Finalidade |
|------|------|------|------|
| Base | `ads_tenants` | BIGINT Snowflake | Multitenant |
| Contas | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | Contas de plataforma OAuth |
| Hierarquia de veiculação | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Veiculação de anúncios |
| Relatórios | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Métricas unificadas |
| Alertas | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Monitoramento de alertas |
| Lances | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Lance automático |
| Segmentação | `ads_targeting_templates` | BIGINT Snowflake | Modelos de público |
| Materiais | `ads_assets` | BIGINT Snowflake | Biblioteca de materiais criativos |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | Configuração de provedor CDN (credenciais criptografadas por campo) |
| Notificações | `ads_notifications` | BIGINT Snowflake | Notificações no sistema |
| Atribuição | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Rastreamento de conversões + atribuição |
| Sistema | `ads_sync_errors` | BIGINT Snowflake | Erros de sincronização |
| Administração | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + auditoria |

### 5.2 Convenção de nomenclatura

- Prefixo de tabela: `ads_`
- Chave primária: `BIGINT UNSIGNED PRIMARY KEY` (sem autoincremento, Snowflake ID)
- Engine: InnoDB, charset: utf8mb4
- Timestamps: `created_at`, `updated_at` (DATETIME)

---

## 6. Arquitetura de segurança

### 6.1 Camadas de proteção

| Camada | Mecanismo | Cobertura |
|----|------|----------|
| Transporte | Nginx (terminação SSL) | Total |
| Rede | Lista de permissões CORS + validação de Origin + HSTS | Service |
| Entrada | AttackGuard (XSS 11 padrões/path traversal 7 padrões/Injeção de Header) | Service + Admin |
| Injeção | SQLGuard (detecção de padrões de injeção de SQL) | Service |
| Limpeza | ValidationMiddleware (strip_tags) | Service |
| Autenticação | JWT Bearer + bcrypt + vínculo IP/UA + rotação de refresh | Service |
| Autenticação | Sessão + JWT em dois canais + CSRF Token | Admin |
| Autorização | RBAC (papéis + JSON de permissões) | Admin |
| Throttle | RateLimit (janela deslizante) + LoginThrottle (5 falhas→15 minutos) | Service + Admin |
| Sessão | SessionLimit (máximo de 3 Tokens ativos) + lista negra | Service |
| Criptografia | EncryptionMiddleware (transporte) + Encryptable (armazenamento) | Service |
| Replay | ReplayGuard (Nonce+Timestamp ±5min, fora do navegador) | Service + clientes |
| Resiliência | CircuitBreaker (por plataforma: 5 falhas → OPEN → 30s semiaberto) + GuardedAdapter (fast-fail de degradação) | Service |
| Auditoria | Trilha de operações (IP/UA/plataforma) | Admin |
| Mascaramento | Mascaramento de campos sensíveis nos logs (password/token/secret → ***) | Service |

### 6.2 Identificação da plataforma do cliente

Através do header `X-Client-Platform`:

| Valor | Origem |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. Roteamento por versão da API

O número da versão da API é fixado no caminho da URL (`/api/v1/...`) e as rotas são vinculadas estaticamente a `plugin\ads_api\controller\v1\*`; não é transmitido no header. Ao adicionar uma nova versão, registre um grupo de rotas independente (ex.: `/api/v2` → `controller\v2\*`).

```
请求: GET /api/v1/campaigns

路由 /api/v1/campaigns
  → controller\v1\CampaignController::index()
```

---

## 8. Agendamento de tarefas

| Tarefa | Cron | Função |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | Atualiza Tokens OAuth expirados |
| DataSyncTask | `*/10 * * * *` | Sincroniza Campaigns→AdGroups→Creatives→Reports→limpa cache |
| AlertCheckTask | `*/5 * * * *` | Avalia regras de alerta e dispara notificações |
| BidCheckTask | `*/10 * * * *` | Avalia regras de lance e executa ajustes de orçamento/início-pausa |
| RetrySyncTask | `*/3 * * * *` | Repete sincronizações com falha (máximo de 3 vezes, backoff exponencial) |

---

## 9. Integração dos pacotes Erik Stack

| Pacote | Local de integração | Finalidade |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 Models (SnowflakeTrait) + admin helpers.php | Geração de chave primária |
| `erikwang2013/hashids` | ApiResponse + 2 Admin Controllers | Codificação de ID |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Token de autenticação |
| `erikwang2013/encryption` | EncryptionMiddleware | Criptografia de transporte |
| `erikwang2013/encryptable` | Modelos PlatformAccount + AuthToken | Criptografia de campos do DB |
| `erikwang2013/webman-scout` | Modelo Campaign (trait Searchable) | Busca ES |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Bandeiras de países |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Captcha deslizante |
| `hg/apidoc` | Anotações → geração de documentação (Web UI: :8788/apidoc) | Documentação da API |

---

## 10. Arquitetura de alta concorrência

### 10.1 Camada de banco de dados

| Otimização | Descrição |
|------|------|
| Separação leitura/escrita | Banco principal `shared` (escrita) + réplica somente leitura `read_replica` (consultas de relatórios/análises) |
| Conexões persistentes | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` para evitar handshakes TCP frequentes |
| Pré-aquecimento de conexões | Executa `SELECT 1` na inicialização do worker; só recebe requisições após o pool de conexões estar pronto |

### 10.2 Camada de cache

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 Fila de mensagens

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 canais: `sync` | `report` | `export` | `notification`

### 10.4 Escala horizontal

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive**: reutilização de 32 conexões longas
- **failover**: `proxy_next_upstream` com failover automático, 2 tentativas
- **Rate limit**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 CDN de recursos estáticos

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — arquivos js/css pré-comprimidos
- Em produção, conecta-se a uma CDN (CloudFront/Aliyun CDN)

### 10.6 Aceleração CDN de materiais

Montagem de URL, estratégias de cache e purge: ver [capítulo 12 Armazenamento de materiais e aceleração CDN](#12-armazenamento-de-materiais-e-aceleração-cdn).

---

## 11. Implantação e CI/CD

### Serviços Docker

| Serviço | Porta | Imagem |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy

---

## 12. Armazenamento de materiais e aceleração CDN

### 12.1 Camada de abstração de armazenamento

`service/plugin/ads-storage/` fornece uma fachada `Storage` unificada + interface `StorageDriver` (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge), alternando a implementação conforme o driver:

| driver | implementação | uso |
|--------|---------------|-----|
| `local` | LocalStorage | Padrão, local `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (protocolo S3) |
| `s3` | S3CompatibleStorage | Compatível S3: AWS S3 / Cloudflare R2 / MinIO |

A distribuição prioriza o provedor padrão no banco (configurável no painel); se não houver, usa env/local.

### 12.2 Gestão de provedores CDN

Nova tabela `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- As credenciais (access_key/secret_key/cdn_token) são criptografadas por campo via `Erikwang2013\Encryptable`; a API só retorna campos mascarados
- Apenas o tenant mestre da plataforma (tenantId=1) pode gerenciar (AdminMiddleware); 8 endpoints em `/api/admin/cdn/providers`: listar/criar/atualizar/excluir/padrão/ativar-desativar/teste de conectividade/purge de cache
- purge está realmente implementado para cdn_driver `aliyun` (assinatura OpenAPI); cloudflare/cloudfront pendentes

### 12.3 Montagem de URL

`ads_assets.url` sempre armazena um caminho relativo (`/uploads/assets/...`); na leitura, o `cdn_domain` do provedor padrão é prefixado formando uma URL HTTPS completa (`https://{cdn_domain}/{url}`); sem CDN, retorna como está.

### 12.4 Estratégia de cache

| tipo | estratégia |
|------|------------|
| imagens | cache longo `immutable` (nomes aleatórios, URLs únicas — seguro) |
| vídeo | cache curto + suporte a Range (reprodução segmentada) |

Ao excluir um material, sua URL é purgada automaticamente do cache CDN.

### 12.5 Isolamento de caminhos multi-tenant

As chaves de materiais têm um prefixo de isolamento por tenant e são agrupadas por tenant_id; materiais de tenants diferentes são invisíveis entre si.

### 12.6 Upload direto pré-assinado e migração

- `POST /api/assets/presign`: obtém uma URL de upload pré-assinada (o cliente envia direto ao armazenamento de objetos, ex. vídeos de 50 MiB); formato de `key`: `Ymd/32hex.extensão`
- `POST /api/assets/register`: registra um material enviado diretamente; o formato de key é validado estritamente contra path traversal
- presign indisponível com o driver `local` (sem assinatura de armazenamento de objetos)
- `service/scripts/backfill-assets.php`: copia materiais locais existentes para o armazenamento de objetos (`--dry-run` para prévia); a coluna `url` permanece inalterada

### 12.7 Caminho de origem

`service/config/static.php` ativa o serviço de arquivos estáticos do webman; `/uploads/assets` é servido diretamente por HTTP na 8788 como caminho de origem do CDN.
