# Relatório de revisão e correção de segurança do Ads-PHP (3ª rodada)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Gerado em**: 2026-08-04  
**Escopo da revisão**: todos os middlewares de segurança, fluxo de autenticação, controlador de instalação e arquivos de configuração  
**Versão do PHP**: 8.3.7 | **Framework**: webman v2  

---

## 1. Visão geral das correções

Esta rodada corrigiu completamente os 6 problemas encontrados na 2ª rodada de revisão de segurança.

| # | Problema | Forma de correção | Status |
|---|------|---------|:--:|
| 1 | Faltam 5 middlewares de segurança no admin | Criados CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Corrigido |
| 2 | O AuthCheck do admin não faz vínculo de IP/UA | AuthController adiciona `_ip` / `_ua` ao payload do JWT, AuthCheck valida o vínculo | Corrigido |
| 3 | Risco de ReDoS no AttackGuardMiddleware | Adicionada verificação prévia `maxStrLen=8192`; strings muito longas são rejeitadas diretamente | Corrigido |
| 4 | Caracteres especiais de senha no InstallController | Adicionado o método `envQuote()` que envolve com aspas e faz escape automaticamente | Corrigido |
| 5 | Configuração de middleware do admin incompleta | Atualizada para uma pilha de 10 camadas de middleware global | Corrigido |
| 6 | Número de camadas de middleware desatualizado no README | README em chinês e inglês atualizados em sincronia | Corrigido |

---

## 2. Validação de sintaxe

| Arquivo | Linhas | Sintaxe |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Aprovado |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Aprovado |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Aprovado |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Aprovado |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Aprovado |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | Aprovado |
| `admin/app/middleware/AuthCheck.php` | 48 | Aprovado |
| `admin/app/controller/AuthController.php` | 194 | Aprovado |
| `admin/app/controller/InstallController.php` | 298 | Aprovado |
| `admin/config/middleware.php` | 43 | Aprovado |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | Aprovado |

---

## 3. Pilha de middlewares (após correção)

### Lado Service (14 camadas globais + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Lado Admin (10 camadas globais + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### Matriz de rotas (após atualização do lado admin)

| Rota | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (protegido) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 4. Detalhes das melhorias de segurança

### 4.1 Novos middlewares no admin

| Middleware | Arquivo | Responsabilidade |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | Preflight CORS + cabeçalhos de resposta; no modo debug libera tudo, em produção usa lista de permissões |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Rate limit com janela deslizante no Redis 60 req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | Detecção de padrões de injeção de SQL (UNION/DROP/ALTER/comentários) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | trim + strip_tags na entrada (exceto description/content/extra) |

### 4.2 Vínculo do JWT Token

O AuthController adiciona `_ip` e `_ua` ao payload do JWT no login:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

O middleware AuthCheck verifica a consistência de IP e UA ao validar o token; se não corresponderem, o acesso é negado.

### 4.3 Proteção contra ReDoS

O AttackGuardMiddleware (admin + service) ganhou `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 Escape de senha no .env

O InstallController ganhou o método `envQuote()`, que detecta caracteres especiais na senha (espaço, `$`, `#`, aspas, barra invertida) e automaticamente envolve com aspas duplas e faz escape.

---

## 5. Lista de arquivos

### Novos (5 arquivos)

| Arquivo | Linhas | Descrição |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Middleware CORS |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Cabeçalhos de segurança |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Rate limit global |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Detecção de injeção de SQL |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Limpeza de entrada |

### Modificados (6 arquivos)

| Arquivo | Alteração |
|------|------|
| `admin/config/middleware.php` | Middleware global de 5→10 camadas |
| `admin/app/middleware/AttackGuardMiddleware.php` | Adicionada proteção contra ReDoS (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Adicionada validação de vínculo JWT IP/UA |
| `admin/app/controller/AuthController.php` | _ip/_ua adicionados ao payload do JWT |
| `admin/app/controller/InstallController.php` | Adicionado escape de senha envQuote() |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Adicionada proteção contra ReDoS (maxStrLen) |

---

## 6. Conclusão

Todos os 6 problemas de segurança foram corrigidos. A defesa do admin passou de 5 para 10 camadas de middleware global, completando 5 proteções-chave: cabeçalhos de segurança, rate limit, detecção de injeção de SQL, limpeza de entrada e CORS. O JWT token ganhou validação de vínculo IP/UA. O risco de ReDoS e o problema de caracteres especiais de senha no .env foram eliminados. Todos os arquivos passaram na verificação de sintaxe do PHP.

