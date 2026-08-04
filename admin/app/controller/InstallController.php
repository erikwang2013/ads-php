<?php
/**
 * One-click web installation wizard.
 *
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Provides:
 *   GET  /install            —  the installation page
 *   POST /api/install/check  —  test database connectivity
 *   POST /api/install/run    —  write .env, run SQL, create admin account
 *   GET  /api/install/status —  check whether already installed
 */

namespace admin\controller;

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
        return dirname(base_path()) . '/install.sql';
    }

    /**
     * Show the installation HTML page, or redirect if already installed.
     */
    public function index(): Response
    {
        if (file_exists(base_path() . '/.installed')) {
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

        $html = file_get_contents(__DIR__ . '/install.html');
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    /**
     * Test database connection with the given credentials.
     */
    public function checkDb(Request $request): Response
    {
        $host     = trim($request->input('db_host', '127.0.0.1'));
        $port     = trim($request->input('db_port', '3306'));
        $database = trim($request->input('db_database', ''));
        $username = trim($request->input('db_username', ''));
        $password = $request->input('db_password', '');

        if (!$database || !$username) {
            return json(['code' => 422, 'message' => '数据库名和用户名不能为空']);
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            new \PDO($dsn, $username, $password, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            return json(['code' => 0, 'message' => '数据库连接成功']);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Access denied')) {
                $msg = '数据库认证失败：用户名或密码错误';
            } elseif (str_contains($msg, 'Unknown database')) {
                $msg = "数据库 '{$database}' 不存在，安装程序将自动创建";
                return json(['code' => 0, 'message' => $msg, 'data' => ['create_db' => true]]);
            } elseif (str_contains($msg, 'Connection refused')) {
                $msg = "无法连接到 {$host}:{$port}，请检查主机地址和端口";
            }
            return json(['code' => 1, 'message' => $msg]);
        }
    }

    /**
     * Run installation: write .env, create database if needed, execute SQL.
     */
    public function run(Request $request): Response
    {
        if (file_exists(base_path() . '/.installed')) {
            return json(['code' => 1, 'message' => '系统已安装，如需重新安装请删除 .installed 文件']);
        }

        $dbHost     = trim($request->input('db_host', '127.0.0.1'));
        $dbPort     = trim($request->input('db_port', '3306'));
        $dbDatabase = trim($request->input('db_database', ''));
        $dbUsername = trim($request->input('db_username', ''));
        $dbPassword = $request->input('db_password', '');

        $redisHost     = trim($request->input('redis_host', '127.0.0.1'));
        $redisPort     = trim($request->input('redis_port', '6379'));
        $redisPassword = $request->input('redis_password', '');

        $serviceApiUrl = trim($request->input('service_api_url', 'http://127.0.0.1:8788/api/v1'));

        $adminUsername = trim($request->input('admin_username', ''));
        $adminPassword = $request->input('admin_password', '');
        $adminName     = trim($request->input('admin_name', '超级管理员'));

        $jwtSecret = bin2hex(random_bytes(32));

        if (!$dbDatabase || !$dbUsername || !$adminUsername || !$adminPassword) {
            return json(['code' => 422, 'message' => '请填写所有必填字段']);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbDatabase)) {
            return json(['code' => 422, 'message' => '数据库名只能包含字母、数字和下划线']);
        }

        if (strlen($adminPassword) < 6) {
            return json(['code' => 422, 'message' => '管理员密码至少6位']);
        }

        // Step 1: create database if needed
        try {
            $dsnNoDb = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new \PDO($dsnNoDb, $dbUsername, $dbPassword, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\PDOException $e) {
            return json(['code' => 1, 'message' => '创建数据库失败：' . $e->getMessage()]);
        }

        // Step 2: write .env
        $env = $this->buildEnv($dbHost, $dbPort, $dbDatabase, $dbUsername, $dbPassword, $redisHost, $redisPort, $redisPassword, $jwtSecret, $serviceApiUrl);
        if (file_put_contents($this->envPath(), $env) === false) {
            return json(['code' => 2, 'message' => '写入 .env 文件失败，请检查目录权限']);
        }

        // Step 3: run install.sql
        $sqlPath = $this->sqlPath();
        if (!file_exists($sqlPath)) {
            return json(['code' => 3, 'message' => 'install.sql 文件不存在：' . $sqlPath]);
        }

        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbDatabase};charset=utf8mb4";
            $pdo = new \PDO($dsn, $dbUsername, $dbPassword, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $sql = file_get_contents($sqlPath);
            $statements = $this->splitSql($sql);

            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') continue;
                $pdo->exec($stmt);
            }
        } catch (\PDOException $e) {
            return json(['code' => 4, 'message' => '执行 SQL 失败：' . $e->getMessage()]);
        }

        // Step 4: update admin password
        try {
            $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
            $stmtSafe = $pdo->prepare("UPDATE admin_users SET password = ?, name = ? WHERE username = 'admin'");
            $stmtSafe->execute([$hash, $adminName]);
        } catch (\PDOException $e) {
            return json(['code' => 5, 'message' => '设置管理员密码失败：' . $e->getMessage()]);
        }

        // Step 5: create lock file
        file_put_contents(base_path() . '/.installed', date('Y-m-d H:i:s'));

        return json([
            'code' => 0,
            'message' => '安装完成',
            'data' => [
                'username' => 'admin',
                'admin_url' => '/',
                'jwt_secret' => $jwtSecret,
            ],
        ]);
    }

    /**
     * Check if already installed.
     */
    public function status(Request $request): Response
    {
        $installed = file_exists(base_path() . '/.installed');
        return json([
            'code' => 0,
            'message' => $installed ? 'installed' : 'not_installed',
            'data' => ['installed' => $installed],
        ]);
    }

    // ------------------------------------------------------------------
    //  helpers
    // ------------------------------------------------------------------

    /**
     * Escape a value for safe inclusion in a .env file.
     * Wraps in double quotes if the value contains special characters.
     */
    private function envQuote(string $value): string
    {
        if ($value === '') {
            return '';
        }
        // If value contains characters that need quoting, wrap and escape
        if (preg_match('/[\s\$\#\"\'\\\\]/', $value)) {
            $escaped = str_replace(['\\', '"', '$'], ['\\\\', '\"', '\$'], $value);
            return '"' . $escaped . '"';
        }
        return $value;
    }

    private function buildEnv(string $dbHost, string $dbPort, string $dbDatabase, string $dbUsername, string $dbPassword, string $redisHost, string $redisPort, string $redisPassword, string $jwtSecret, string $serviceApiUrl): string
    {
        $dbPasswordQuoted     = $this->envQuote($dbPassword);
        $redisPasswordQuoted  = $this->envQuote($redisPassword);
        $serviceApiUrlQuoted  = $this->envQuote($serviceApiUrl);

        return <<<EOF
APP_DEBUG=false
APP_URL=http://0.0.0.0:8789

SERVICE_API_URL={$serviceApiUrlQuoted}

DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_DATABASE={$dbDatabase}
DB_USERNAME={$dbUsername}
DB_PASSWORD={$dbPasswordQuoted}

REDIS_HOST={$redisHost}
REDIS_PORT={$redisPort}
REDIS_PASSWORD={$redisPasswordQuoted}

JWT_SECRET={$jwtSecret}
JWT_TTL=86400
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
