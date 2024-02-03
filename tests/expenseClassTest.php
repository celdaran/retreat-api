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
}
