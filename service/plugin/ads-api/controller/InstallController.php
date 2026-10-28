<?php
/**
 * One-click web installation wizard for the service backend.
 *
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Routes (registered outside the auth group in config/route.php):
 *   GET  /install            — the installation page
 *   POST /api/install/check  — test database connectivity
 *   POST /api/install/run    — create database, run install.sql, write .env
 *   GET  /api/install/status — check whether already installed
 */

namespace plugin\ads_api\controller;

use Webman\Http\Request;
use Webman\Http\Response;

class InstallController
{
    private function envPath(): string
    {
        return base_path() . '/.env';
    }

    private function sqlPath(): string
    {
        // Docker image: /app/install.sql; repo checkout: <repo>/install.sql.
        foreach ([base_path() . '/install.sql', dirname(base_path()) . '/install.sql', '/install.sql'] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return base_path() . '/install.sql';
    }

    private function tokenPath(): string
    {
        return base_path() . '/runtime/install-token.txt';
    }

    /**
     * One-time token proving filesystem access to the server, so a remote
     * caller cannot take over an uninstalled instance or use it as a DB probe.
     * Returns null when the runtime directory is not writable.
     */
    private function installToken(): ?string
    {
        $path = $this->tokenPath();
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        // 'x' creates atomically; on collision read the winner's token.
        $fp = @fopen($path, 'x');
        if ($fp !== false) {
            $token = bin2hex(random_bytes(16));
            $written = fwrite($fp, $token);
            fclose($fp);
            if ($written !== strlen($token) || !@chmod($path, 0600)) {
                @unlink($path);
                return null;
            }
            return $token;
        }

        $token = trim((string) @file_get_contents($path));
        if ($token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(16));
        if (@file_put_contents($path, $token) === false || !@chmod($path, 0600)) {
            @unlink($path);
            return null;
        }
        return $token;
    }

    private function runtimeError(): Response
    {
        return new Response(500, ['Content-Type' => 'application/json'], json_encode([
            'code'    => 500,
            'message' => 'runtime 目录不可写，无法生成安装令牌',
        ], JSON_UNESCAPED_UNICODE));
    }

    private function requireToken(Request $request): ?Response
    {
        $expected = trim((string) @file_get_contents($this->tokenPath()));
        if ($expected === '') {
            return new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'code'    => 500,
                'message' => '安装令牌文件不存在或不可读，请重新打开安装页面并检查 runtime 目录权限',
            ], JSON_UNESCAPED_UNICODE));
        }

        $given = trim((string) $request->input('install_token', ''));
        if ($given === '' || !hash_equals($expected, $given)) {
            return new Response(403, ['Content-Type' => 'application/json'], json_encode([
                'code'    => 403,
                'message' => '安装令牌无效或缺失。请读取 runtime/install-token.txt'
                    . '（Docker：docker compose exec php cat runtime/install-token.txt）',
            ], JSON_UNESCAPED_UNICODE));
        }
        return null;
    }

    private function validHost(string $host): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9.\-:]+$/', $host);
    }

    private function validPort(string $port): bool
    {
        return ctype_digit($port) && (int) $port >= 1 && (int) $port <= 65535;
    }

    private function logError(string $message, \Throwable $e): void
    {
        \support\Log::channel('default')->error($message, ['error' => $e->getMessage()]);
    }

    /**
     * Installed when the lock file exists, or when a manual .env already
     * carries an API password hash.
     */
    private function isInstalled(): bool
    {
        return file_exists(base_path() . '/.installed') || trim((string) env('API_PASSWORD_HASH', '')) !== '';
    }

    /**
     * Show the installation page, or an "already installed" notice.
     */
    public function index(): Response
    {
        if ($this->isInstalled()) {
            $html = <<<'HTML'
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><title>已安装 — Ads-PHP</title>
<style>
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; background:#f0f2f5; }
.card { background:#fff; border-radius:8px; padding:48px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.08); max-width:400px; }
h2 { color:#52c41a; margin-bottom:8px; }
p { color:#999; margin-bottom:24px; }
a { display:inline-block; padding:10px 28px; background:#2d8cf0; color:#fff; border-radius:6px; text-decoration:none; font-weight:500; }
</style></head>
<body>
<div class="card">
<h2>&#10003; 已安装</h2>
<p>系统已完成安装，无需重复操作。</p>
<a href="/">进入管理后台</a>
</div>
</body></html>
HTML;
            return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
        }

        if ($this->installToken() === null) {
            return $this->runtimeError();
        }

        $html = (string) file_get_contents(__DIR__ . '/install.html');
        $html = str_replace(
            ['{{DB_HOST}}', '{{DB_PORT}}', '{{REDIS_HOST}}', '{{REDIS_PORT}}'],
            array_map(
                fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
                [env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), env('REDIS_HOST', '127.0.0.1'), env('REDIS_PORT', '6379')]
            ),
            $html
        );
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    /**
     * Test database connection with the given credentials.
     */
    public function checkDb(Request $request): Response
    {
        if ($denied = $this->requireToken($request)) {
            return $denied;
        }

        $host     = trim((string) $request->input('db_host', '127.0.0.1'));
        $port     = trim((string) $request->input('db_port', '3306'));
        $database = trim((string) $request->input('db_database', ''));
        $username = trim((string) $request->input('db_username', ''));
        $password = (string) $request->input('db_password', '');

        if (!$database || !$username) {
            return json(['code' => 422, 'message' => '数据库名和用户名不能为空']);
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            return json(['code' => 422, 'message' => '数据库名只能包含字母、数字和下划线']);
        }
        if (!$this->validHost($host)) {
            return json(['code' => 422, 'message' => '数据库主机地址格式不正确']);
        }
        if (!$this->validPort($port)) {
            return json(['code' => 422, 'message' => '数据库端口必须为 1-65535']);
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            new \PDO($dsn, $username, $password, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            return json(['code' => 0, 'message' => '数据库连接成功']);
        } catch (\PDOException $e) {
            $this->logError('installer: database check failed', $e);
            if (str_contains($e->getMessage(), 'Unknown database')) {
                return json(['code' => 0, 'message' => "数据库 '{$database}' 不存在，安装程序将自动创建", 'data' => ['create_db' => true]]);
            }
            if (str_contains($e->getMessage(), 'Access denied')) {
                return json(['code' => 1, 'message' => '数据库认证失败：用户名或密码错误']);
            }
            return json(['code' => 1, 'message' => '数据库连接失败，请检查主机地址、端口和凭据']);
        }
    }

    /**
     * Run installation: create database, execute install.sql, write .env.
     */
    public function run(Request $request): Response
    {
        if ($this->isInstalled()) {
            return json(['code' => 1, 'message' => '系统已安装，如需重新安装请删除 .installed 并清空 .env 中的 API_PASSWORD_HASH']);
        }

        if ($denied = $this->requireToken($request)) {
            return $denied;
        }

        $dbHost     = trim((string) $request->input('db_host', '127.0.0.1'));
        $dbPort     = trim((string) $request->input('db_port', '3306'));
        $dbDatabase = trim((string) $request->input('db_database', ''));
        $dbUsername = trim((string) $request->input('db_username', ''));
        $dbPassword = (string) $request->input('db_password', '');

        $redisHost     = trim((string) $request->input('redis_host', '127.0.0.1'));
        $redisPort     = trim((string) $request->input('redis_port', '6379'));
        $redisPassword = (string) $request->input('redis_password', '');

        $adminUsername = trim((string) $request->input('admin_username', ''));
        $adminPassword = (string) $request->input('admin_password', '');
        $adminName     = trim((string) $request->input('admin_name', '管理员'));

        if (!$dbDatabase || !$dbUsername || !$adminUsername || !$adminPassword) {
            return json(['code' => 422, 'message' => '请填写所有必填字段']);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbDatabase)) {
            return json(['code' => 422, 'message' => '数据库名只能包含字母、数字和下划线']);
        }

        if (!$this->validHost($dbHost)) {
            return json(['code' => 422, 'message' => '数据库主机地址格式不正确']);
        }
        if (!$this->validPort($dbPort)) {
            return json(['code' => 422, 'message' => '数据库端口必须为 1-65535']);
        }
        if (!$this->validHost($redisHost)) {
            return json(['code' => 422, 'message' => 'Redis 主机地址格式不正确']);
        }
        if (!$this->validPort($redisPort)) {
            return json(['code' => 422, 'message' => 'Redis 端口必须为 1-65535']);
        }

        if (strlen($adminPassword) < 12) {
            return json(['code' => 422, 'message' => '管理员密码至少12位']);
        }

        // Step 1: connect to the target database. Docker creates it via MYSQL_DATABASE
        // and the app user only has privileges on it, so CREATE DATABASE must not run
        // there. Create it only when it is genuinely missing (root/manual installs).
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbDatabase};charset=utf8mb4";
        try {
            $pdo = new \PDO($dsn, $dbUsername, $dbPassword, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            if (!str_contains($e->getMessage(), 'Unknown database')) {
                $this->logError('installer: database connection failed', $e);
                return json(['code' => 1, 'message' => '数据库连接失败，请检查数据库凭据与权限']);
            }

            try {
                $pdo = new \PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUsername, $dbPassword, [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new \PDO($dsn, $dbUsername, $dbPassword, [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (\PDOException $e) {
                $this->logError('installer: create database failed', $e);
                return json(['code' => 1, 'message' => '创建数据库失败，请检查数据库凭据与权限']);
            }
        }

        // Step 2: execute install.sql (idempotent; .env is written only after
        // this succeeds so a failed run can be retried)
        $sqlPath = $this->sqlPath();
        if (!file_exists($sqlPath)) {
            return json(['code' => 3, 'message' => 'install.sql 文件不存在：' . $sqlPath]);
        }

        try {
            foreach ($this->splitSql((string) file_get_contents($sqlPath)) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') {
                    continue;
                }
                $pdo->exec($stmt);
            }
        } catch (\PDOException $e) {
            $this->logError('installer: install.sql execution failed', $e);
            return json(['code' => 4, 'message' => '执行 SQL 失败，请检查数据库版本与权限']);
        }

        // Step 3: write .env
        $env = $this->buildEnv([
            'app_url'          => $this->appUrl($request),
            'api_username'     => $adminUsername,
            'api_password_hash'=> password_hash($adminPassword, PASSWORD_BCRYPT),
            'api_name'         => $adminName,
            'jwt_secret'       => bin2hex(random_bytes(32)),
            // base64: prefix → Encrypter decodes to the 32 bytes aes-256-gcm needs.
            'encryption_key'   => 'base64:' . base64_encode(random_bytes(32)),
            'app_encryption_key' => 'base64:' . base64_encode(random_bytes(32)),
            'db_host'          => $dbHost,
            'db_port'          => $dbPort,
            'db_database'      => $dbDatabase,
            'db_username'      => $dbUsername,
            'db_password'      => $dbPassword,
            'redis_host'       => $redisHost,
            'redis_port'       => $redisPort,
            'redis_password'   => $redisPassword,
        ]);
        // Atomic: never leave a truncated .env if the write fails mid-way.
        $envPath = $this->envPath();
        $tmpPath = $envPath . '.tmp';
        $written = @file_put_contents($tmpPath, $env);
        if ($written !== strlen($env)) {
            @unlink($tmpPath);
            return json(['code' => 2, 'message' => '写入 .env 文件失败，请检查目录权限']);
        }
        if (!@chmod($tmpPath, 0600)) {
            @unlink($tmpPath);
            return json(['code' => 2, 'message' => '设置 .env 文件权限失败，请检查目录权限']);
        }
        if (!@rename($tmpPath, $envPath)) {
            @unlink($tmpPath);
            return json(['code' => 2, 'message' => '写入 .env 文件失败，请检查目录权限']);
        }

        // Step 4: lock the installer, then retire the one-time token
        if (@file_put_contents(base_path() . '/.installed', date('Y-m-d H:i:s')) === false) {
            return json(['code' => 2, 'message' => '写入 .installed 文件失败，请检查目录权限']);
        }
        @unlink($this->tokenPath());

        return json([
            'code'    => 0,
            'message' => '安装完成',
            'data'    => [
                'username'  => $adminUsername,
                'admin_url' => '/',
                'restart'   => 'php start.php restart',
            ],
        ]);
    }

    /**
     * Check if already installed.
     */
    public function status(Request $request): Response
    {
        $installed = $this->isInstalled();
        if (!$installed && $this->installToken() === null) {
            return $this->runtimeError();
        }
        return json([
            'code'    => 0,
            'message' => $installed ? 'installed' : 'not_installed',
            'data'    => ['installed' => $installed],
        ]);
    }

    // ------------------------------------------------------------------
    //  helpers
    // ------------------------------------------------------------------

    /**
     * Absolute URL of this service instance, used for OAuth callbacks.
     * Behind nginx, X-Forwarded-Proto carries the original scheme.
     */
    private function appUrl(Request $request): string
    {
        $host = (string) $request->host();
        if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
            return 'http://127.0.0.1:8788';
        }
        $proto = strtolower(trim(explode(',', (string) $request->header('x-forwarded-proto', 'http'))[0]));
        return ($proto === 'https' ? 'https' : 'http') . '://' . $host;
    }

    /**
     * Escape a value for safe inclusion in a .env file.
     * Wraps in double quotes if the value contains special characters.
     */
    private function envQuote(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/[\s\$\#\"\'\\\\]/', $value)) {
            $escaped = str_replace(['\\', '"', '$'], ['\\\\', '\"', '\$'], $value);
            return '"' . $escaped . '"';
        }
        return $value;
    }

    private function buildEnv(array $cfg): string
    {
        $appUrl          = $this->envQuote($cfg['app_url']);
        $oauthRedirects  = $this->envQuote($cfg['app_url'] . '/*');
        $apiUsername     = $this->envQuote($cfg['api_username']);
        $apiPasswordHash = $this->envQuote($cfg['api_password_hash']);
        $apiName         = $this->envQuote($cfg['api_name']);
        $appEncryptionKey = $this->envQuote($cfg['app_encryption_key']);
        $encryptionKey   = $this->envQuote($cfg['encryption_key']);
        $dbHost          = $this->envQuote($cfg['db_host']);
        $dbPort          = $this->envQuote($cfg['db_port']);
        $dbUsername      = $this->envQuote($cfg['db_username']);
        $dbPassword      = $this->envQuote($cfg['db_password']);
        $redisHost       = $this->envQuote($cfg['redis_host']);
        $redisPort       = $this->envQuote($cfg['redis_port']);
        $redisPassword   = $this->envQuote($cfg['redis_password']);

        return <<<EOF
APP_DEBUG=false
APP_URL={$appUrl}
OAUTH_ALLOWED_REDIRECTS={$oauthRedirects}
CAPTCHA_ENABLED=true

API_USERNAME={$apiUsername}
API_PASSWORD_HASH={$apiPasswordHash}
API_NAME={$apiName}

JWT_SECRET={$cfg['jwt_secret']}
JWT_TTL=86400
JWT_STORAGE=redis

# Field-level encryption uses ENCRYPTION_KEY / ENCRYPTION_CIPHER.
# APP_ENCRYPTION_KEY is only for the X-Encrypted body middleware.
APP_ENCRYPTION_KEY={$appEncryptionKey}
ENCRYPTION_KEY={$encryptionKey}
ENCRYPTION_CIPHER=aes-256-gcm

DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_DATABASE={$cfg['db_database']}
DB_USERNAME={$dbUsername}
DB_PASSWORD={$dbPassword}

REDIS_HOST={$redisHost}
REDIS_PORT={$redisPort}
REDIS_PASSWORD={$redisPassword}
EOF;
    }

    /**
     * Split a multi-statement SQL string respecting string literals.
     */
    private function splitSql(string $sql): array
    {
        $statements = [];
        $current = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $current .= $ch;

            if ($ch === '\\') {
                if ($i + 1 < $len) {
                    $current .= $sql[$i + 1];
                    $i++;
                }
                continue;
            }

            if ($ch === "'" && !$inDouble && !$inBacktick) {
                $inSingle = !$inSingle;
            } elseif ($ch === '"' && !$inSingle && !$inBacktick) {
                $inDouble = !$inDouble;
            } elseif ($ch === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
            } elseif ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
                $statements[] = $current;
                $current = '';
            }
        }

        $current = trim($current);
        if ($current !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}
