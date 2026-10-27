<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz — https://erik.xyz
 *
 * bc_round()/bc_money() 助手回归测试（项目规则：价格/数据计算一律 bcmath）。
 * 覆盖：半离零四舍五入、浮点陷阱值、负数、整数金额半单位进位 bug。
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MoneyCalcTest extends TestCase
{
    public function testBcRoundHalfAwayFromZero(): void
    {
        $this->assertSame('9800.00', bc_round(9800, 2));           // 整数金额不得被加半单位
        $this->assertSame('50.0', bc_round('50.0000', 1));
        $this->assertSame('1.24', bc_round('1.2449', 2));          // 截断而非进位
        $this->assertSame('1.25', bc_round('1.245', 2));           // 恰好半数 → 进位（半离零）
        $this->assertSame('100.0', bc_round('99.96', 1));          // 阈值进位
        $this->assertSame('2.68', bc_round('2.675', 2));           // 浮点 round 得 2.67 的经典陷阱
        $this->assertSame('0.00', bc_round(-0.004, 2));            // 负数小值 → 0，无 -0.00
        $this->assertSame('-1.24', bc_round('-1.244', 2));
    }

    public function testBcMoneyKeepsNumberFormatOutput(): void
    {
        $this->assertSame('1,234.56', bc_money(123456));
        $this->assertSame('50.00', bc_money(5000));
        $this->assertSame('0.01', bc_money(1));
        $this->assertSame('0.00', bc_money(0));
    }
}
