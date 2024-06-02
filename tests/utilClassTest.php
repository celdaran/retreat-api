<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Util;
use App\Service\Engine\Until;

final class utilClassTest extends TestCase
{
    public function testCalculateInterest10k0(): void
    {
        $interest = Util::calculateInterest(10000, 0);
        $this->assertEquals(0, $interest);
    }

    public function testCalculateInterest10k1(): void
    {
        $interest = Util::calculateInterest(10000, 1);
        $this->assertEquals(8, $interest);
    }

    public function testCalculateInterest10k4333(): void
    {
        $interest = Util::calculateInterest(10000, 4.333);
        $this->assertEquals(36, $interest);
    }

    public function testCalculateInterest250k6125(): void
    {
        $interest = Util::calculateInterest(250000, 6.125);
        $this->assertEquals(1276, $interest);
    }

    public function testPeriodCompareBoth(): void
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

    public function testPeriodCompareNulls(): void
    {
        $cmp = Util::periodCompare(2030, 6, NULL, NULL);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(2000, 1, NULL, NULL);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(1999, 1, NULL, NULL);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(1970, 2, NULL, NULL);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(1970, 1, NULL, NULL);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(1969, 12, NULL, NULL);
        $this->assertEquals(-1, $cmp);

        $cmp = Util::periodCompare(1960, 1, NULL, NULL);
        $this->assertEquals(-1, $cmp);
    }

    public function testFormatDollars(): void
    {
        $f = Util::usd(null);
        $this->assertEquals('$0', $f);

        $f = Util::usd("0");
        $this->assertEquals('$0', $f);

        $f = Util::usd("1");
        $this->assertEquals('$1', $f);

        $f = Util::usd("1.25");
        $this->assertEquals('$1', $f);

        $f = Util::usd("1.75");
        $this->assertEquals('$2', $f);

        $f = Util::usd("one dollar");
        $this->assertEquals('$0', $f);

        $f = Util::usd(new Until());
        $this->assertEquals('$0', $f);

        $f = Util::usd(0);
        $this->assertEquals('$0', $f);

        $f = Util::usd(1);
        $this->assertEquals('$1', $f);

        $f = Util::usd(10);
        $this->assertEquals('$10', $f);

        $f = Util::usd(10);
        $this->assertEquals('$10', $f);

        $f = Util::usd(10.123);
        $this->assertEquals('$10', $f);

        $f = Util::usd(10.789);
        $this->assertEquals('$11', $f);

        $f = Util::usd(17.50);
        $this->assertEquals('$18', $f);

        $f = Util::usd(1000);
        $this->assertEquals('$1,000', $f);

        $f = Util::usd(1234567);
        $this->assertEquals('$1,234,567', $f);

        $f = Util::usd(1234567.89);
        $this->assertEquals('$1,234,568', $f);
    }

}
