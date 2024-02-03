<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Util;

final class utilClassTest extends TestCase
{
    public function testCalculateInterest10k0(): void
    {
        $interest = Util::calculateInterest(10000, 0);
        $this->assertEquals(0, $interest->value());
    }

    public function testCalculateInterest10k1(): void
    {
        $interest = Util::calculateInterest(10000, 1);
        $this->assertEquals(8.33, $interest->value());
    }

    public function testCalculateInterest10k4333(): void
    {
        $interest = Util::calculateInterest(10000, 4.333);
        $this->assertEquals(36.11, $interest->value());
    }

    public function testCalculateInterest250k6125(): void
    {
        $interest = Util::calculateInterest(250000, 6.125);
        $this->assertEquals(1276.04, $interest->value());
    }

    public function testPeriodCompare(): void
    {
        $cmp = Util::periodCompare(2026, 1, 2026, 1);
        $this->assertEquals(0, $cmp);

        $cmp = Util::periodCompare(2026, 1, 2025, 1);
        $this->assertEquals(1, $cmp);

        $cmp = Util::periodCompare(2025, 1, 2026, 1);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(2025, 1, 2025, 2);
        $this->assertEquals(-1, $cmp);
    }
}
