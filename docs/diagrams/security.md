# 安全架构图

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 纵深防御架构

```mermaid
graph TB
    subgraph Perimeter["外层防御 — Nginx :80"]
        SSL["SSL 终结<br/>HTTPS 强制"]
        NginxRL["分级限流<br/>30r/s + burst 20"]
        ConnLimit["并发连接限制<br/>limit_conn 20"]
        KeepAlive["keepalive 32<br/>长连接复用"]
        StaticCache["静态资源缓存<br/>expires 30d + immutable"]
    end
    subgraph Gate["入口守卫 — Service :8788 全局中间件"]
        direction TB
        CORS["CORS<br/>生产域名白名单<br/>OPTIONS 预检"]
        OG["OriginGuard<br/>Origin/Referer 校验<br/>拦截 TRACE/DEBUG/CONNECT"]
        SH["SecurityHeaders<br/>CSP · X-Frame-Options<br/>X-Content-Type-Options<br/>HSTS max-age=31536000"]
        AG["AttackGuard<br/>XSS 11模式<br/>路径遍历 7模式<br/>Header CRLF注入<br/>Body 10MiB限制<br/>Content-Type白名单"]
    end
    subgraph AuthN["身份认证层"]
        direction TB
        CAP["滑块验证码<br/>poster-php<br/>5px容差·5min有效<br/>登录保护"]
        LT["登录节流<br/>5次失败→15分钟锁定<br/>Redis 计数+TTL<br/>LoginThrottleMiddleware"]
        BC["bcrypt 密码哈希<br/>password_verify()<br/>admin_users 表"]
        JWT["JWT Bearer Token<br/>24h TTL<br/>IP + User-Agent hash绑定<br/>AuthMiddleware"]
        TB["Token 黑名单<br/>刷新后旧Token作废<br/>Redis Set 存储"]
        SL["并发会话限制<br/>每用户最大3个活跃Token<br/>SessionLimitMiddleware"]
        RP["防重放攻击<br/>Nonce + Timestamp ±5min<br/>ReplayGuardMiddleware<br/>非浏览器端强校验"]
    end
    subgraph Validation["输入校验层"]
        SQL["SQLGuard<br/>UNION/DROP/ALTER<br/>SLEEP/BENCHMARK<br/>模式检测"]
        Val["Validation<br/>trim + strip_tags<br/>输入清洗"]
        CSRF["CSRF Token<br/>Admin端Session比对<br/>CsrfMiddleware"]
        SSRF["SSRF防护<br/>OAuth redirect_uri<br/>域名白名单校验"]
    end
    subgraph RateLimit["频率控制层"]
        RL["滑动窗口限流<br/>60次/60s<br/>Redis Sorted Set<br/>RateLimitMiddleware"]
        RT["响应时间监控<br/>X-Response-Time<br/>慢请求日志 >1s<br/>ResponseTimeMiddleware"]
        CB["平台调用熔断<br/>5次失败→OPEN<br/>30s半开探活<br/>CircuitBreaker"]
    end
    subgraph Encryption["数据加密层"]
        TransEnc["传输加密<br/>X-Encrypted AES<br/>EncryptionMiddleware<br/>请求解密·响应加密"]
        StoreEnc["存储加密<br/>DB字段级<br/>Encryptable Trait<br/>platform_accounts<br/>auth_tokens"]
        LogMask["日志脱敏<br/>password/token/secret<br/>→ ***<br/>Monolog Processor"]
    end
    subgraph Audit["审计追溯层"]
        AuditLog["操作审计<br/>user_id · username<br/>action · resource<br/>detail · ip · ua<br/>client_platform"]
        Confirm["二次确认<br/>删除/解绑/批量<br/>输入确认词模式<br/>GlobalConfirm<br/>useConfirmStore"]
    end

    Perimeter --> Gate
    Gate --> AuthN
    AuthN --> Validation
    Validation --> RateLimit
    RateLimit --> CB
    CB --> Encryption
    Encryption --> Audit
```

## Admin 端安全架构

```mermaid
graph LR
    subgraph AdminGate["Admin :8789 — 9 层中间件"]
        direction TB
        A1["CORS"] --> A2["SecurityHeaders"]
        A2 --> A3["AttackGuard"]
        A3 --> A4["ClientPlatform"]
        A4 --> A5["RateLimit"]
        A5 --> A6["LoginThrottle"]
        A6 --> A7["SQLGuard"]
        A7 --> A8["Validation"]
        A8 --> A9["CSRF"]
        A9 --> AuthCheck["AuthCheck<br/>Session + JWT 双通道"]
    end
```

## 安全能力矩阵

| 层次 | 中间件 | Service | Admin | 核心技术 |
|------|--------|---------|-------|----------|
| 网络 | Nginx | ✅ | ✅ | SSL · limit_req · limit_conn |
| 跨域 | CorsMiddleware | ✅ | ✅ | 域名白名单 |
| 来源 | OriginGuardMiddleware | ✅ | — | Origin/Referer校验 |
| 响应头 | SecurityHeadersMiddleware | ✅ | ✅ | CSP · HSTS · XFO · XCTO |
| 攻击检测 | AttackGuardMiddleware | ✅ | ✅ | XSS 11 · 路径遍历 7 · Header注入 |
| 客户端 | ClientPlatformMiddleware | ✅ | ✅ | 8端来源识别 |
| 防重放 | ReplayGuardMiddleware | ✅ | — | Nonce + Timestamp ±5min |
| 限流 | RateLimitMiddleware | ✅ | ✅ | Redis滑动窗口 60/60s |
| 登录节流 | LoginThrottleMiddleware | ✅ | ✅ | 5次→15min Redis |
| 会话限制 | SessionLimitMiddleware | ✅ | — | 最大3活跃Token |
| SQL注入 | SqlGuardMiddleware | ✅ | ✅ | 模式检测 |
| 输入清洗 | ValidationMiddleware | ✅ | ✅ | trim + strip_tags |
| CSRF | CsrfMiddleware | — | ✅ | Session Token比对 |
| 响应时间 | ResponseTimeMiddleware | ✅ | — | X-Response-Time |
| 传输加密 | EncryptionMiddleware | ✅ | — | AES 加解密 |
| 认证 | AuthMiddleware / AuthCheck | ✅ | ✅ | JWT · Session双通道 · IP/UA绑定 |
| 审计 | AuditService | — | ✅ | 操作轨迹记录 |
| 确认 | GlobalConfirm | ✅ | ✅ | 输入确认词模式 |
| 弹性 | CircuitBreaker + GuardedAdapter | ✅ | — | 5次失败→OPEN, 30s半开, per-platform 降级 |

## 22 项防护能力总览

| # | 分类 | 能力 | 机制 |
|---|------|------|------|
| 1 | 输入检测 | XSS 防护 | 11种模式正则匹配 |
| 2 | | 路径遍历防护 | 7种模式检测 |
| 3 | | Header 注入防护 | CRLF 检测 |
| 4 | | Body 大小限制 | 10 MiB 上限 |
| 5 | | Content-Type 白名单 | JSON/Form/Multipart/Plain |
| 6 | | SQL 注入防护 | UNION/DROP/ALTER 模式 |
| 7 | 认证 | JWT Token 绑定 | IP + User-Agent hash |
| 8 | | Token 刷新 + 黑名单 | 旧Token自动失效 |
| 9 | | 登录节流 | 5次失败→15分钟Redis |
| 10 | | 并发会话限制 | 最多3活跃Token |
| 11 | | 验证码 | 滑块5px容差·5min有效 |
| 12 | 请求校验 | CORS 白名单 | 生产域名白名单 |
| 13 | | Origin/Referer 校验 | 跨域来源验证 |
| 14 | | CSRF Token | Session token验证 |
| 15 | | 防重放攻击 | Nonce+Timestamp±5min |
| 16 | | 接口限流 | 滑动窗口60次/60s |
| 17 | | SSRF 防护 | OAuth redirect_uri白名单 |
| 18 | 响应头 | CSP | Content-Security-Policy |
| 19 | | X-Frame-Options / HSTS | 防点击劫持+HTTPS强制 |
| 20 | | X-Content-Type-Options | nosniff |
| 21 | 数据保护 | 传输加密 | EncryptionMiddleware AES |
| 22 | | 存储加密 | Encryptable DB字段级 |
| 23 | | 日志脱敏 | password/token→*** |
