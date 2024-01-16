<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Period;

final class periodClassTest extends TestCase
{
    public function testDefaults(): void
    {
        $period = new Period();

        $year = (int)date('Y');
        $month = (int)date('m');
        $currentPeriod = 1;

        $this->assertEquals($year, $period->getYear());
        $this->assertEquals($month, $period->getMonth());
        $this->assertEquals($currentPeriod, $period->getCurrentPeriod());
    }

    public function testUserSpecified(): void
    {
        $period = new Period(2029, 5, 7);

        $this->assertEquals(2029, $period->getYear());
        $this->assertEquals(5, $period->getMonth());
        $this->assertEquals(7, $period->getCurrentPeriod());
    }

    public function testAdvancePeriod(): void
    {
        $period = new Period(2020, 1);

        $this->assertEquals(2020, $period->getYear());
        $this->assertEquals(1, $period->getMonth());
        $this->assertEquals(1, $period->getCurrentPeriod());

        $period->advance();

        $this->assertEquals(2020, $period->getYear());
        $this->assertEquals(2, $period->getMonth());
        $this->assertEquals(2, $period->getCurrentPeriod());

        $period->advance();

        $this->assertEquals(2020, $period->getYear());
        $this->assertEquals(3, $period->getMonth());
        $this->assertEquals(3, $period->getCurrentPeriod());

        $period->advance();
        $period->advance();
        $period->advance();
        $period->advance();
        $period->advance();
        $period->advance();
        $period->advance();
        $period->advance();
        $period->advance();

        $this->assertEquals(2020, $period->getYear());
        $this->assertEquals(12, $period->getMonth());
        $this->assertEquals(12, $period->getCurrentPeriod());

        $period->advance();

        $this->assertEquals(2021, $period->getYear());
        $this->assertEquals(1, $period->getMonth());
        $this->assertEquals(13, $period->getCurrentPeriod());

    }

    public function testAddMonths(): void
    {
        $period = new Period(2020, 1);

        $this->assertEquals(2020, $period->getYear());
        $this->assertEquals(1, $period->getMonth());
        $this->assertEquals(1, $period->getCurrentPeriod());

        $newPeriod = $period->addMonths($period->getYear(), $period->getMonth(), 1);

        $this->assertEquals(2020, $newPeriod->getYear());
        $this->assertEquals(2, $newPeriod->getMonth());
        $this->assertEquals(2, $newPeriod->getCurrentPeriod());

        $newPeriod = $period->addMonths($period->getYear(), $period->getMonth(), 12);

        $this->assertEquals(2021, $newPeriod->getYear());
        $this->assertEquals(1, $newPeriod->getMonth());
        $this->assertEquals(13, $newPeriod->getCurrentPeriod());

        $newPeriod = $period->addMonths($period->getYear(), $period->getMonth(), 749);

        $this->assertEquals(2082, $newPeriod->getYear());
        $this->assertEquals(6, $newPeriod->getMonth());
        $this->assertEquals(750, $newPeriod->getCurrentPeriod());
    }
}
