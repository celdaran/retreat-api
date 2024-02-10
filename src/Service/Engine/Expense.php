<?php namespace App\Service\Engine;

/**
 * A class representing an expense
 */
class Expense
{

    const PLANNED = 0;
    const ACTIVE = 1;
    const ENDED = 9;

    protected string $name;
    protected Money $amount;
    protected float $inflationRate;
    protected ?int $beginYear;
    protected ?int $beginMonth;
    protected ?int $endYear;
    protected ?int $endMonth;
    protected ?int $repeatEvery;
    protected int $status;

    public function __construct()
    {
        $this->status = self::PLANNED;
    }

    //--------------------------------------------
    // Setters
    //--------------------------------------------

    public function setName(string $name): Expense
    {
        $this->name = $name;
        return $this;
    }

    public function setAmount(Money $amount): Expense
    {
        $this->amount = $amount;
        return $this;
    }

    public function increaseAmount(float $n): Expense
    {
        $this->amount->add($n);
        return $this;
    }

    public function setInflationRate(float $inflationRate): Expense
    {
        $this->inflationRate = round($inflationRate, 3);
        return $this;
    }

    public function setBeginYear(?int $beginYear): Expense
    {
        $this->beginYear = $beginYear;
        return $this;
    }

    public function getBeginYear(): ?int
    {
        return $this->beginYear;
    }

    public function setBeginMonth(?int $beginMonth): Expense
    {
        $this->beginMonth = $beginMonth;
        return $this;
    }

    public function getBeginMonth(): ?int
    {
        return $this->beginMonth;
    }

    public function setEndYear(?int $endYear): Expense
    {
        $this->endYear = $endYear;
        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndMonth(?int $endMonth): Expense
    {
        $this->endMonth = $endMonth;
        return $this;
    }

    public function getEndMonth(): ?int
    {
        return $this->endMonth;
    }

    public function setRepeatEvery(?int $repeatEvery): Expense
    {
        $this->repeatEvery = $repeatEvery;
        return $this;
    }

    public function markPlanned(): Expense
    {
        $this->status = self::PLANNED;
        return $this;
    }

    public function markActive(): Expense
    {
        $this->status = self::ACTIVE;
        return $this;
    }

    public function markEnded(): Expense
    {
        $this->status = self::ENDED;
        return $this;
    }

    //--------------------------------------------
    // Getters
    //--------------------------------------------

    public function name(): string
    {
        return $this->name;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function inflationRate(): float
    {
        return $this->inflationRate;
    }

    public function beginYear(): int
    {
        return $this->beginYear;
    }

    public function beginMonth(): int
    {
        return $this->beginMonth;
    }

    public function endYear(): ?int
    {
        return $this->endYear;
    }

    public function endMonth(): ?int
    {
        return $this->endMonth;
    }

    public function repeatEvery(): ?int
    {
        return $this->repeatEvery;
    }

    public function isPlanned(): bool
    {
        return $this->status === self::PLANNED;
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function isEnded(): bool
    {
        return $this->status === self::ENDED;
    }

    public function status(): string
    {
        return match ($this->status) {
            self::PLANNED => 'planned',
            self::ACTIVE => 'active',
            self::ENDED => 'ended',
            default => 'unknown',
        };
    }

    public function timeToActivate(Period $period): bool
    {
        if ($this->isPlanned()) {
            $compare = Util::periodCompare(
                $period->getYear(), $period->getMonth(),
                $this->beginYear(), $this->beginMonth()
            );
            if ($compare >= 0) {
                return true;
            }
        }

        return false;
    }

    /** @noinspection DuplicatedCode */
    public function timeToEnd(Period $period): string
    {
        if ($this->isActive()) {
            // Compare dates
            if ($this->endYear() === null) {
                $compare = -1;
            } else {
                $compare = Util::periodCompare(
                    $period->getYear(), $period->getMonth(),
                    $this->endYear(), $this->endMonth()
                );
            }

            // Must check repeating to determine next steps
            if ($this->repeatEvery() === null) {
                if ($compare >= 0) {
                    return 'yep';
                } else {
                    return 'nope';
                }
            } else {
                if ($compare >= 0) {
                    return 'yep';
                } else {
                    return 'reschedule';
                }
            }
        }

        return 'nope';
    }

    public function reschedule()
    {
        // Can't reschedule something that's over
        if ($this->isEnded()) {
            return;
        }

        // Get next period
        $period = new Period();
        $nextPeriod = $period->addMonths(
            $this->beginYear(), $this->beginMonth(),
            $this->repeatEvery());

        // Set new begin year and month
        $this->setBeginYear($nextPeriod->getYear());
        $this->setBeginMonth($nextPeriod->getMonth());

        // Has the rescheduling expired?
        $cmp = Util::periodCompare(
            $this->getBeginYear(), $this->getBeginMonth(),
            $this->getEndYear(), $this->getEndMonth(),
        );

        if ($cmp >= 0) {
            // If so, end the expense
            $this->markEnded();
        } else {
            // Otherwise revert to planning state
            $this->markPlanned();
        }
    }
}
