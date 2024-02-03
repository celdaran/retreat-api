<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Money;

final class moneyClassTest extends TestCase
{
    public function testDefaults(): void
    {
        $money = new Money();

        $this->assertEquals(0.00, $money->value());
        $this->assertEquals("$0.00", $money->formatted());

        $money->assign(1.00);
        $this->assertEquals(1.00, $money->value());
        $this->assertEquals("$1.00", $money->formatted());

        $money->assign(1.001);
        $this->assertEquals(1.00, $money->value());
        $this->assertEquals("$1.00", $money->formatted());

        $money->assign(1.009);
        $this->assertEquals(1.01, $money->value());
        $this->assertEquals("$1.01", $money->formatted());
    }

    public function testAdd(): void
    {
        $money = new Money(5.001);

        $money->add(1.00);
        $this->assertEquals(6.00, $money->value());
        $this->assertEquals("$6.00", $money->formatted());

        $money->add(10.00);
        $this->assertEquals(16.00, $money->value());
        $this->assertEquals("$16.00", $money->formatted());

        $money->add(120.20 / 2.38);
        $this->assertEquals(66.50, $money->value());
        $this->assertEquals("$66.50", $money->formatted());

        $money->add(2.9934 * 3.0001);
        $this->assertEquals(75.48, $money->value());
        $this->assertEquals("$75.48", $money->formatted());
    }

    public function testSubtract(): void
    {
        $money = new Money(100.00);

        $money->subtract(1.00);
        $this->assertEquals(99.00, $money->value());
        $this->assertEquals("$99.00", $money->formatted());

        $money->subtract(10.00);
        $this->assertEquals(89.00, $money->value());
        $this->assertEquals("$89.00", $money->formatted());

        $money->subtract(120.20 / 2.38);
        $this->assertEquals(38.50, $money->value());
        $this->assertEquals("$38.50", $money->formatted());

        $money->subtract(2.9934 * 3.0001);
        $this->assertEquals(29.52, $money->value());
        $this->assertEquals("$29.52", $money->formatted());
    }

    public function testComparisonOperators(): void
    {
        $a = new Money();
        $b = new Money();

        $this->assertEquals(true, $a->eq($b->value()));

        $a->assign(3.0001);
        $b->assign(3.0048);

        $this->assertEquals(true, $a->eq($b->value()));

        $a->assign(10.002);
        $b->assign(10.005);

        $this->assertEquals(false, $a->eq($b->value()));

        $a->assign(5);
        $b->assign(6);

        $this->assertEquals(true, $a->le($b->value()));

        $a->assign(5);
        $b->assign(5);

        $this->assertEquals(true, $a->le($b->value()));

        $a->assign(6);
        $b->assign(5);

        $this->assertEquals(false, $a->le($b->value()));
    }
}
