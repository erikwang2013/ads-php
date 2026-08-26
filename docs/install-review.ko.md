# Ads-PHP 보안 검토 및 수정 보고서 (3차)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**생성 시간**: 2026-08-04  
**검토 범위**: 전체 보안 미들웨어, 인증 흐름, 설치 컨트롤러, 설정 파일  
**PHP 버전**: 8.3.7 | **프레임워크**: webman v2  

---

## 1. 수정 개요

이번 차수에서는 2차 보안 검토에서 발견된 6개 문제를 전면 수정했습니다.

| # | 문제 | 수정 방식 | 상태 |
|---|------|---------|:--:|
| 1 | admin 측에 보안 미들웨어 5개 부재 | CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware 신규 생성 | 수정 완료 |
| 2 | admin AuthCheck가 IP/UA 바인딩을 하지 않음 | AuthController JWT payload에 `_ip` / `_ua` 추가, AuthCheck에서 바인딩 검증 | 수정 완료 |
| 3 | AttackGuardMiddleware ReDoS 위험 | `maxStrLen=8192` 사전 검사 추가, 초장문자열은 직접 거부 | 수정 완료 |
| 4 | InstallController 비밀번호 특수 문자 | `envQuote()` 메서드 추가, 자동 인용부호 처리 + 이스케이프 | 수정 완료 |
| 5 | admin 미들웨어 설정 불완전 | 10계층 글로벌 미들웨어 스택으로 업데이트 | 수정 완료 |
| 6 | README 미들웨어 계층 수 부실 | 중/영문 README 동기 업데이트 | 수정 완료 |

---

## 2. 문법 검증

| 파일 | 줄 수 | 문법 |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | 통과 |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | 통과 |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | 통과 |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | 통과 |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | 통과 |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | 통과 |
| `admin/app/middleware/AuthCheck.php` | 48 | 통과 |
| `admin/app/controller/AuthController.php` | 194 | 통과 |
| `admin/app/controller/InstallController.php` | 298 | 통과 |
| `admin/config/middleware.php` | 43 | 통과 |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | 통과 |

---

## 3. 미들웨어 스택 (수정 후)

### Service 측 (14계층 글로벌 + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（라우트 계층）
```

### Admin 측 (10계층 글로벌 + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（라우트 계층）
```

### 라우트 매트릭스 (admin 측 업데이트 후)

| 라우트 | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (보호) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 4. 보안 개선 상세

### 4.1 admin 신규 미들웨어

| 미들웨어 | 파일 | 역할 |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS 사전 검사 + 응답 헤더, debug 모드 전면 허용, 프로덕션 화이트리스트 |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis 슬라이딩 윈도우 속도 제한 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL 주입 패턴 검출（UNION/DROP/ALTER/주석 기호） |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | 입력 trim + strip_tags（description/content/extra 제외） |

### 4.2 JWT Token 바인딩

AuthController 로그인 시 JWT payload에 `_ip`와 `_ua` 추가:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

AuthCheck 미들웨어가 token 검증 시 IP와 UA 일치 여부를 확인하며, 불일치하면 접근을 거부합니다.

### 4.3 ReDoS 방어

AttackGuardMiddleware（admin + service）에 `maxStrLen = 8192` 추가:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env 비밀번호 이스케이프

InstallController에 `envQuote()` 메서드를 추가하여 비밀번호의 특수 문자（공백, `$`, `#`, 인용부호, 백슬래시）를 감지하고, 자동으로 큰따옴표 처리 + 이스케이프합니다.

---

## 5. 파일 목록

### 신규 (5 파일)

| 파일 | 줄 수 | 설명 |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS 미들웨어 |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | 보안 응답 헤더 |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | 전역 속도 제한 |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL 주입 검출 |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | 입력 정제 |

### 수정 (6 파일)

| 파일 | 변경 사항 |
|------|------|
| `admin/config/middleware.php` | 글로벌 미들웨어 5→10계층 |
| `admin/app/middleware/AttackGuardMiddleware.php` | ReDoS 방어 추가（maxStrLen） |
| `admin/app/middleware/AuthCheck.php` | JWT IP/UA 바인딩 검증 추가 |
| `admin/app/controller/AuthController.php` | JWT payload에 _ip/_ua 추가 |
| `admin/app/controller/InstallController.php` | envQuote() 비밀번호 이스케이프 추가 |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | ReDoS 방어 추가（maxStrLen） |

---

## 6. 결론

6개 보안 문제가 모두 수정되었습니다. admin 측 방어가 5계층에서 10계층 글로벌 미들웨어로 증가하여 보안 응답 헤더, 속도 제한, SQL 주입 검출, 입력 정제, CORS 등 5개 핵심 방어를 보완했습니다. JWT token에 IP/UA 바인딩 검증이 추가되었습니다. ReDoS 위험과 .env 비밀번호 특수 문자 문제가 해소되었습니다. 모든 파일이 PHP 문법 검사를 통과했습니다.
