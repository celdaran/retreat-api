<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Expense;
use App\Service\Engine\Period;

final class expenseClassTest extends TestCase
{
    public function testTimeToActivate(): void
    {
        $expense = new Expense();
        $expense->setBeginYear(2026);
        $expense->setBeginMonth(7);

        $period = new Period(2025, 1);
        $timeToActivate = $expense->timeToActivate($period);
        $status = $expense->status();
        $this->assertEquals(false, $timeToActivate);
        $this->assertEquals('planned', $status);

        $period = new Period(2026, 1);
        $timeToActivate = $expense->timeToActivate($period);
        $status = $expense->status();
        $this->assertEquals(false, $timeToActivate);
        $this->assertEquals('planned', $status);

        $period = new Period(2027, 1);
        $timeToActivate = $expense->timeToActivate($period);
        $this->assertEquals(true, $timeToActivate);
        if ($timeToActivate === true) {
            $expense->markActive();
            $status = $expense->status();
            $this->assertEquals('active', $status);
        }
    }

    public function testTimeToActivateEnd(): void
    {
        $expense = new Expense();
        $expense->setBeginYear(2026);
        $expense->setBeginMonth(7);
        $expense->setEndYear(2028);
        $expense->setEndMonth(6);

        $period = new Period(2026, 1);
        $timeToActivate = $expense->timeToActivate($period);
        $status = $expense->status();
        $this->assertEquals(false, $timeToActivate);
        $this->assertEquals('planned', $status);

        $period = new Period(2027, 1);
        $timeToActivate = $expense->timeToActivate($period);
        $this->assertEquals(true, $timeToActivate);
        if ($timeToActivate === true) {
            $expense->markActive();
            $status = $expense->status();
            $this->assertEquals('active', $status);
        }

        $period = new Period(2029, 1);
        $timeToActivate = $expense->timeToActivate($period);
        $this->assertEquals(false, $timeToActivate);
        if ($timeToActivate === false) {
            $expense->markEnded();
            $status = $expense->status();
            $this->assertEquals('ended', $status);
        }
    }

    public function testRepeatEvery(): void
    {
        $period = new Period(2025, 1);

        $expense = new Expense();
        $expense->setBeginYear($period->getYear());
        $expense->setBeginMonth($period->getMonth());
        $expense->setEndYear(9999);
        $expense->setEndMonth(12);
        $expense->setRepeatEvery(6);

        for ($i = 0; $i < 36; $i++) {
            $timeToActivate = $expense->timeToActivate($period);
            if ($i % 6 === 0) {
                $this->assertEquals(true, $timeToActivate);
                $expense->markActive();
            } else {
                $this->assertEquals(false, $timeToActivate);
                if ($i % 6 === 1) {
                    $nextPeriod = $period->addMonths(
                        $expense->beginYear(), $expense->beginMonth(),
                        $expense->repeatEvery());
                    $expense->setBeginYear($nextPeriod->getYear());
                    $expense->setBeginMonth($nextPeriod->getMonth());
                    $expense->markPlanned();
                }
            }
            $period->advance();
        }
    }

    public function testReschedule(): void
    {
        $expense = new Expense();
        $expense
            ->setName('Unit Test')
            ->setAmount(100)
            ->setInflationRate(0.000)
            ->setBeginYear(2030)
            ->setBeginMonth(8)
            ->setEndYear(2040)
            ->setEndMonth(7)
            ->setRepeatEvery(39)
            ->markPlanned();

        $expense->reschedule();
        $this->assertEquals(2033, $expense->getBeginYear());
        $this->assertEquals(11, $expense->getBeginMonth());
        $this->assertTrue($expense->isPlanned());

        $expense->reschedule();
        $this->assertEquals(2037, $expense->getBeginYear());
        $this->assertEquals(2, $expense->getBeginMonth());
        $this->assertTrue($expense->isPlanned());

        $expense->reschedule();
        $this->assertEquals(2040, $expense->getBeginYear());
        $this->assertEquals(5, $expense->getBeginMonth());
        $this->assertTrue($expense->isPlanned());

        $expense->reschedule();
        $this->assertEquals(2043, $expense->getBeginYear());
        $this->assertEquals(8, $expense->getBeginMonth());
        $this->assertFalse($expense->isActive());

        $expense->reschedule();
        $this->assertEquals(2043, $expense->getBeginYear());
        $this->assertEquals(8, $expense->getBeginMonth());
        $this->assertFalse($expense->isActive());
    }
}
