<?php namespace App\Service\Engine;

use Exception;

class Until
{
    const PERIODS_END = 1;
    const ASSETS_DEPLETE = 2;

    private int $until;
    private int $periods;

    public function __construct(int $until = Until::PERIODS_END)
    {
        $this->setUntil($until);
    }

    /**
     * @return int
     */
    public function getUntil(): int
    {
        return $this->until;
    }

    /**
     * @param int $until
     */
    public function setUntil(int $until): Until
    {
        $this->until = $until;
        return $this;
    }

    /**
     * @return int
     */
    public function getPeriods(): int
    {
        return $this->periods;
    }

    /**
     * @param int $periods
     */
    public function setPeriods(int $periods): Until
    {
        $this->periods = $periods;
        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function toString(): string
    {
        return match ($this->getUntil()) {
            Until::PERIODS_END => sprintf("%d periods pass", $this->getPeriods()),
            Until::ASSETS_DEPLETE => "assets deplete",
            default => throw new Exception("Invalid Until mode: " . $this->getUntil()),
        };
    }

    /**
     * @param Period $period
     * @param Money $shortfall
     * @return bool
     * @throws Exception
     */
    public function unsatisfied(Period $period, Money $shortfall): bool
    {
        $satisfied = false;

        // Check for requested number of periods to elapse
        if ($this->getUntil() === Until::PERIODS_END) {
            if ($period->getCurrentPeriod() > $this->getPeriods()) {
                $satisfied = true;
            }
        }

        // Check for asset depletion
        if ($this->getUntil() === Until::ASSETS_DEPLETE) {
            if ($shortfall->gt(0.00)) {
                $satisfied = true;
            }
        }

        // Failsafe (never loop more than a century)
        if ($period->getCurrentPeriod() > 1200) {
            $satisfied = true;
        }

        return ! $satisfied;
    }
}
