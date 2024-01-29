<?php namespace App\Service\Engine;

/**
 * A class for handling periods
 */
class Period
{

    protected int $year;
    protected int $month;
    protected int $currentPeriod;

    public function __construct(int $year = null, int $month = null, int $currentPeriod = null)
    {
        $this->year = $year === null ? ((int)date('Y')) : $year;
        $this->month = $month === null ? ((int)date('m')) : $month;
        $this->currentPeriod = $currentPeriod === null ? 1 : $currentPeriod;
    }

    public function advance()
    {
        $newPeriod = $this->_advance($this->year, $this->month);
        $this->year = $newPeriod->getYear();
        $this->month = $newPeriod->getMonth();
        $this->currentPeriod++;
    }

    public function addMonths(int $year, int $month, int $numberOfMonths): Period
    {
        return $this->_advance($year, $month, $numberOfMonths);
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getCurrentPeriod(): int
    {
        return $this->currentPeriod;
    }

    private function _advance(int $year, int $month, int $numberOfMonths = 1): Period
    {
        // First move years ahead by an integer number of years
        $newYear = $year + intdiv($numberOfMonths, 12);

        // Then add modulus of above as months
        $newMonth = $month + $numberOfMonths % 12;

        // If we went over, then roll to the next year
        if ($newMonth > 12) {
            $newMonth -= 12;
            $newYear++;
        }

        return new Period($newYear, $newMonth, $numberOfMonths + 1);
    }

}
