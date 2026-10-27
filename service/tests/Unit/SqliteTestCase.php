<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 单元测试 SQLite 基类：将全局默认连接切换为内存 SQLite，
 * 使 Service/Model 层逻辑可在不依赖真实 MySQL 的情况下验证。
 * tearDown 恢复原连接状态，避免污染其它测试。
 */

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SQLiteSchemaGrammar;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use PDO;
use Tests\TestCase;

abstract class SqliteTestCase extends TestCase
{
    protected \Illuminate\Database\Connection $conn;

    protected ?\Illuminate\Database\Query\Grammars\Grammar $origQueryGrammar = null;
    protected ?\Illuminate\Database\Schema\Grammars\Grammar $origSchemaGrammar = null;
    protected ?\Illuminate\Database\Query\Processors\Processor $origProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conn = DB::connection();
        // 记录原连接状态（测试进程共享同一 Connection，tearDown 必须完整还原，
        // 否则遗留的 SQLite grammar 会让后续 Integration 测试对 MySQL 生成
        // 双引号标识符 SQL 而报 1064 语法错误）
        $this->origQueryGrammar = $this->conn->getQueryGrammar();
        $this->origSchemaGrammar = $this->conn->getSchemaGrammar();
        $this->origProcessor = $this->conn->getPostProcessor();

        // setPdo 不会触发真实 MySQL 连接（连接是惰性建立的）
        $this->conn->setPdo(new PDO('sqlite::memory:'));
        $this->conn->setQueryGrammar(new SQLiteGrammar());
        $this->conn->setSchemaGrammar(new SQLiteSchemaGrammar());
        $this->conn->setPostProcessor(new SQLiteProcessor());
        $this->conn->setTablePrefix('');
    }

    protected function tearDown(): void
    {
        $this->conn->setPdo(null);
        $this->conn->setQueryGrammar($this->origQueryGrammar);
        // 首次测试时 schemaGrammar 尚未初始化（Connection 构造时不建 schema grammar），
        // 原值为 null，setSchemaGrammar 参数不可为 null → 用反射还原
        if ($this->origSchemaGrammar === null) {
            (new \ReflectionProperty(\Illuminate\Database\Connection::class, 'schemaGrammar'))
                ->setValue($this->conn, null);
        } else {
            $this->conn->setSchemaGrammar($this->origSchemaGrammar);
        }
        $this->conn->setPostProcessor($this->origProcessor);
        $this->conn->setTablePrefix('');
        parent::tearDown();
    }

    /** 执行一条建表/初始化 SQL（作用于内存 SQLite） */
    protected function exec(string $sql): void
    {
        $this->conn->getPdo()->exec($sql);
    }
}
