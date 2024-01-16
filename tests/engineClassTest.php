<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Engine;
use App\Service\Engine\Money;

final class engineClassTest extends TestCase
{
    public function testEngineAlpha1(): void
    {
        $engine = new Engine('alpha');
        $engine->run(1);

        $plan = $engine->getPlan();

        $expected = 1;
        $actual = count($plan);
        $this->assertEquals($expected, $actual, "Engine ran for one period");
    }

    public function testEngineAlpha5(): void
    {
        $engine = new Engine('alpha');
        $engine->run(5);

        $plan = $engine->getPlan();

        $expected = 5;
        $actual = count($plan);
        $this->assertEquals($expected, $actual, "Engine ran for five periods");

        $expectedEntry = [
            'assets' => [
                'asset' => 25000.00,
            ],
            'expense' => new Money(1000.00),
            'net_expense' => new Money(1000.00),
            'income' => [],
            'month' => 0,
            'period' => 0,
            'year' => 2020,
        ];

        $annualIncome = new Money();

        foreach(range(0, 4) as $i) {

            $annualIncome->add($expectedEntry['expense']->value());
            $period = $i + 1;

            $expectedEntry['assets']['asset'] -= 1000.00;
            $expectedEntry['month'] += 1;
            $expectedEntry['period'] = $period;

            // Add income tax!
            if ($period % 4 === 0) {
                $taxAmount = round($annualIncome->value() * 0.18, 2);
                $expectedEntry['assets']['asset'] -= $taxAmount;
                $expectedEntry['expense']->add($taxAmount);
                $expectedEntry['net_expense']->add($taxAmount);
            } else {
                $expectedEntry['expense']->assign(1000.00);
                $expectedEntry['net_expense']->assign(1000.00);
            }

            $this->assertEquals($expectedEntry, $plan[$i], "Test breaks here");

        }
    }

    public function testEngineAlpha12(): void
    {
        $engine = new Engine('alpha');
        $engine->run(12);

        $plan = $engine->getPlan();

        $expected = 12;
        $actual = count($plan);
        $this->assertEquals($expected, $actual, "Engine ran for one year");

        $expectedEntry = [
            'assets' => [
                'asset' => 12280.00,
            ],
            'expense' => new Money(1000.00),
            'net_expense' => new Money(1000.00),
            'income' => [],
            'month' => 12,
            'period' => 12,
            'year' => 2020,
        ];

        $this->assertEquals($expectedEntry, $plan[11]);
    }

    public function testEngineAlpha22(): void
    {
        $engine = new Engine('alpha');
        $engine->run(22);

        $plan = $engine->getPlan();

        $expected = 22;
        $actual = count($plan);
        $this->assertEquals($expected, $actual, "Engine ran for 22 months");

        $expectedEntry = [
            'assets' => [
                'asset' => 120.00,
            ],
            'expense' => new Money(1000.00),
            'net_expense' => new Money(1000.00),
            'income' => [],
            'month' => 10,
            'period' => 22,
            'year' => 2021,
        ];

        $this->assertEquals($expectedEntry, $plan[21]);
    }

    public function testEngineBravo1(): void
    {
        $engine = new Engine('bravo');
        $engine->run(1);

        $plan = $engine->getPlan();

        $expected = 1;
        $actual = count($plan);
        $this->assertEquals($expected, $actual, "Engine ran for 1 months");
    }
}
