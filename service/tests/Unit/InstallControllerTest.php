<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 安装器 SQL 拆分回归：字符串/反引号内的分号不得截断语句；
 * 并校验仓库 install.sql 不含 DELIMITER/PROCEDURE/wa_（PDO 逐条执行不支持）。
 */

namespace Tests\Unit;

use Dotenv\Dotenv;
use Erikwang2013\Encryptable\Contracts\EncryptableConfigContract;
use Erikwang2013\Encryptable\PHPEncrypter;
use plugin\ads_api\controller\InstallController;
use ReflectionMethod;
use Tests\TestCase;
use Webman\Http\Request;

class InstallControllerTest extends TestCase
{
    private function split(string $sql): array
    {
        $method = new ReflectionMethod(InstallController::class, 'splitSql');
        return $method->invoke(new InstallController(), $sql);
    }

    public function testSplitSqlKeepsSemicolonsInsideQuotedStrings(): void
    {
        $sql = "INSERT INTO t VALUES ('a;b'); INSERT INTO t VALUES (\"c;d\"); SELECT 1;";
        $statements = array_values(array_filter(array_map('trim', $this->split($sql))));

        $this->assertCount(3, $statements);
        $this->assertSame("INSERT INTO t VALUES ('a;b');", $statements[0]);
        $this->assertSame('INSERT INTO t VALUES ("c;d");', $statements[1]);
        $this->assertSame('SELECT 1;', $statements[2]);
    }

    public function testSplitSqlHandlesEscapedQuotesAndBackticks(): void
    {
        $sql = "INSERT INTO t VALUES ('it\\'s; fine'); SELECT `a;b` FROM t; SELECT 2";

        $statements = array_values(array_filter(array_map('trim', $this->split($sql))));

        $this->assertCount(3, $statements);
        $this->assertStringContainsString("it\\'s; fine", $statements[0]);
        $this->assertSame('SELECT `a;b` FROM t;', $statements[1]);
        $this->assertSame('SELECT 2', $statements[2]); // 末尾无分号的语句也保留
    }

    public function testBuildEnvWritesEncryptableCompatibleKeys(): void
    {
        $keyA = 'base64:' . base64_encode(random_bytes(32));
        $keyB = 'base64:' . base64_encode(random_bytes(32));
        $hash = password_hash('secret123', PASSWORD_BCRYPT);

        $method = new ReflectionMethod(InstallController::class, 'buildEnv');
        $raw = $method->invoke(new InstallController(), [
            'app_url' => 'http://localhost', 'api_username' => 'admin',
            'api_password_hash' => $hash, 'api_name' => '管理员',
            'jwt_secret' => bin2hex(random_bytes(32)),
            'encryption_key' => $keyA, 'app_encryption_key' => $keyB,
            'db_host' => 'mysql', 'db_port' => '3306', 'db_database' => 'ads',
            'db_username' => 'root', 'db_password' => 'root',
            'redis_host' => 'redis', 'redis_port' => '6379', 'redis_password' => '',
        ]);
        $env = Dotenv::parse($raw);

        $this->assertSame('aes-256-gcm', $env['ENCRYPTION_CIPHER']);
        $this->assertSame('http://localhost/*', $env['OAUTH_ALLOWED_REDIRECTS']);
        $this->assertSame(32, strlen((string) base64_decode(substr($env['ENCRYPTION_KEY'], 7), true)));
        $this->assertSame(32, strlen((string) base64_decode(substr($env['APP_ENCRYPTION_KEY'], 7), true)));
        $this->assertTrue(password_verify('secret123', $env['API_PASSWORD_HASH']));
        $this->assertArrayNotHasKey('ADMIN_USERNAME', $env);
        $this->assertArrayNotHasKey('SERVICE_API_URL', $env);

        // Field encryption reads ENCRYPTION_KEY; APP_ENCRYPTION_KEY feeds the
        // body middleware, which sha256-derives any non-32-byte key.
        foreach ([$env['ENCRYPTION_KEY'], $env['APP_ENCRYPTION_KEY']] as $key) {
            $config = new class($key) implements EncryptableConfigContract {
                public function __construct(private string $key) {}
                public function getKey(): ?string { return $this->key; }
                public function getCipher(): ?string { return 'aes-256-gcm'; }
                public function getPreviousKeys(): array { return []; }
            };
            $encrypter = new PHPEncrypter($config);
            $this->assertSame('oauth-token', $encrypter->decrypt($encrypter->encrypt('oauth-token')));
        }
    }

    public function testHostAndPortValidation(): void
    {
        $controller = new InstallController();
        $host = new ReflectionMethod(InstallController::class, 'validHost');
        $port = new ReflectionMethod(InstallController::class, 'validPort');

        foreach (['mysql', '127.0.0.1', 'db-1.internal', 'redis', '::1'] as $value) {
            $this->assertTrue($host->invoke($controller, $value), $value);
        }
        foreach (['', 'bad host', 'bad;host', 'host/path'] as $value) {
            $this->assertFalse($host->invoke($controller, $value), $value);
        }
        foreach (['1', '3306', '65535'] as $value) {
            $this->assertTrue($port->invoke($controller, $value), $value);
        }
        foreach (['0', '65536', 'abc', '-1', ''] as $value) {
            $this->assertFalse($port->invoke($controller, $value), $value);
        }
    }

    public function testInstallerRejectsRequestWithoutToken(): void
    {
        // 令牌文件存在但请求未带/带错令牌 -> 403；令牌文件缺失 -> 500 明确提示
        $controller = new InstallController();
        $tokenPath = base_path() . '/runtime/install-token.txt';
        $hadToken = is_file($tokenPath);
        $originalToken = $hadToken ? (string) file_get_contents($tokenPath) : null;
        $createToken = new ReflectionMethod(InstallController::class, 'installToken');

        try {
            $createToken->invoke($controller);

            $body = $this->jsonBody($controller->checkDb($this->postRequest([])));
            $this->assertSame(403, $body['code']);
            $this->assertStringContainsString('安装令牌', $body['message']);

            $body = $this->jsonBody($controller->checkDb($this->postRequest(['install_token' => 'wrong-token'])));
            $this->assertSame(403, $body['code']);

            unlink($tokenPath);
            $response = $controller->checkDb($this->postRequest([]));
            $this->assertSame(500, $response->getStatusCode());
            $this->assertStringContainsString('令牌文件不存在', $this->jsonBody($response)['message']);

            // run() 需要令牌文件存在才能进入 403 分支；先判断已安装，临时清空密码哈希
            $createToken->invoke($controller);
            $previousHash = getenv('API_PASSWORD_HASH');
            putenv('API_PASSWORD_HASH=');
            try {
                $response = $controller->run($this->postRequest([]));
                $this->assertSame(403, $response->getStatusCode());
                $this->assertSame(403, $this->jsonBody($response)['code']);
            } finally {
                putenv($previousHash === false ? 'API_PASSWORD_HASH=' : 'API_PASSWORD_HASH=' . $previousHash);
            }
        } finally {
            // 测试不得在 runtime/ 留下安装令牌
            if ($hadToken) {
                @file_put_contents($tokenPath, (string) $originalToken);
                @chmod($tokenPath, 0600);
            } else {
                @unlink($tokenPath);
            }
        }
    }

    private function postRequest(array $payload): Request
    {
        $body = json_encode($payload);
        return new Request("POST /api/install/check HTTP/1.1\r\nHost: localhost\r\n"
            . "Content-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body);
    }

    private function jsonBody(\Webman\Http\Response $response): array
    {
        return json_decode($response->rawBody(), true) ?? [];
    }

    public function testRepositoryInstallSqlHasNoStoredPrograms(): void
    {
        $path = null;
        foreach ([dirname(__DIR__, 3) . '/install.sql', dirname(__DIR__, 2) . '/install.sql'] as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }
        $this->assertNotNull($path, 'install.sql not found');

        $sql = (string) file_get_contents($path);
        $this->assertDoesNotMatchRegularExpression('/\bDELIMITER\b|\bPROCEDURE\b|wa_/i', $sql);
    }
}
