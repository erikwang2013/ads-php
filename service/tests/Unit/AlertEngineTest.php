<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * AlertEngine 测试：compare 条件语义（反射）、METRIC_SQL 指标覆盖、
 * 未知指标不触发；SQLite 上验证完整 evaluate 流程（触发 + 去重抑制）。
 */

namespace Tests\Unit;

use plugin\ads_alert\model\AlertLog;
use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\service\AlertEngine;

class AlertEngineTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->exec('CREATE TABLE erik_report_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT,
            campaign_id INT, date TEXT, cost INT, impressions INT, clicks INT, conversions INT)');
        $this->exec('CREATE TABLE erik_alert_rules (
            id TEXT PRIMARY KEY, tenant_id INT, name TEXT, metric TEXT, threshold REAL,
            condition TEXT, scope TEXT, platform TEXT, campaign_id INT, check_interval INT,
            channels TEXT, webhook_url TEXT, enabled INT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_alert_logs (
            id TEXT PRIMARY KEY, tenant_id INT, rule_id TEXT, rule_name TEXT, metric TEXT,
            current_value REAL, threshold REAL, condition TEXT, status TEXT, extra TEXT,
            created_at TEXT)');
    }

    private function rule(array $overrides = []): AlertRule
    {
        return new AlertRule(array_merge([
            'id'             => 'r1',
            'tenant_id'      => 1,
            'name'           => 'cost spike',
            'metric'         => 'cost',
            'threshold'      => 50,
            'condition'      => 'gt',
            'scope'          => 'tenant',
            'platform'       => null,
            'campaign_id'    => null,
            'check_interval' => 10,
            'enabled'        => true,
        ], $overrides));
    }

    public function testCompareConditionSemantics(): void
    {
        $engine = new AlertEngine();
        $compare = new \ReflectionMethod(AlertEngine::class, 'compare');
        $compare->setAccessible(true);

        $this->assertTrue($compare->invoke($engine, 100.0, 50.0, 'gt'));
        $this->assertFalse($compare->invoke($engine, 50.0, 50.0, 'gt'));
        $this->assertTrue($compare->invoke($engine, 50.0, 50.0, 'gte'));
        $this->assertTrue($compare->invoke($engine, 30.0, 50.0, 'lt'));
        $this->assertTrue($compare->invoke($engine, 50.0, 50.0, 'lte'));
        $this->assertFalse($compare->invoke($engine, 100.0, 50.0, 'eq'));
    }

    public function testMetricSqlCoversAllMetrics(): void
    {
        $const = new \ReflectionClassConstant(AlertEngine::class, 'METRIC_SQL');
        $sql = $const->getValue();

        foreach (['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'roi'] as $m) {
            $this->assertArrayHasKey($m, $sql, "missing METRIC_SQL entry for {$m}");
        }
    }

    public function testEvaluateReturnsNullForUnknownMetric(): void
    {
        $engine = new AlertEngine();
        $this->assertNull($engine->evaluate($this->rule(['metric' => 'unknown'])));
    }

    public function testEvaluateTriggersLogWhenThresholdExceeded(): void
    {
        \Illuminate\Database\Capsule\Manager::table('erik_report_metrics')->insert([
            ['tenant_id' => 1, 'platform' => 'juliang', 'campaign_id' => 11, 'date' => date('Y-m-d'), 'cost' => 100, 'impressions' => 1000, 'clicks' => 10, 'conversions' => 2],
            ['tenant_id' => 1, 'platform' => 'juliang', 'campaign_id' => 12, 'date' => date('Y-m-d'), 'cost' => 40, 'impressions' => 500, 'clicks' => 5, 'conversions' => 1],
        ]);

        $engine = new AlertEngine();
        $log = $engine->evaluate($this->rule(['condition' => 'gt', 'threshold' => 100]));

        $this->assertInstanceOf(AlertLog::class, $log);
        $this->assertSame(140.0, $log->current_value);
        $this->assertSame('triggered', $log->status);
        $this->assertSame('cost', $log->metric);
    }

    public function testEvaluateDoesNotTriggerBelowThreshold(): void
    {
        \Illuminate\Database\Capsule\Manager::table('erik_report_metrics')->insert([
            'tenant_id' => 1, 'platform' => 'juliang', 'date' => date('Y-m-d'), 'cost' => 10,
        ]);

        $engine = new AlertEngine();
        // 10 > 500 不成立、10 < 5 不成立 → 均不触发
        $this->assertNull($engine->evaluate($this->rule(['condition' => 'gt', 'threshold' => 500])));
        $this->assertNull($engine->evaluate($this->rule(['condition' => 'lt', 'threshold' => 5])));
    }

    public function testEvaluateSuppressesDuplicateWithinCheckInterval(): void
    {
        \Illuminate\Database\Capsule\Manager::table('erik_report_metrics')->insert([
            'tenant_id' => 1, 'platform' => 'juliang', 'date' => date('Y-m-d'), 'cost' => 100,
        ]);

        $engine = new AlertEngine();
        $rule = $this->rule(['condition' => 'gt', 'threshold' => 50]);

        $this->assertInstanceOf(AlertLog::class, $engine->evaluate($rule));
        // 同一检查间隔内重复触发 → 抑制
        $this->assertNull($engine->evaluate($rule));
    }

    public function testEvaluateScopeCampaignFiltersByCampaign(): void
    {
        \Illuminate\Database\Capsule\Manager::table('erik_report_metrics')->insert([
            ['tenant_id' => 1, 'campaign_id' => 11, 'date' => date('Y-m-d'), 'cost' => 200],
            ['tenant_id' => 1, 'campaign_id' => 12, 'date' => date('Y-m-d'), 'cost' => 5],
        ]);

        $engine = new AlertEngine();
        $rule = $this->rule(['scope' => 'campaign', 'campaign_id' => 12, 'condition' => 'gt', 'threshold' => 100]);

        $this->assertNull($engine->evaluate($rule)); // campaign 12 累计仅 5
    }
}
