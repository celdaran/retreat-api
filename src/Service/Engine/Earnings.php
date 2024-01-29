<?php namespace App\Service\Engine;

/**
 * A class representing a source of earnings
 */
class Earnings
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

    //--------------------------------------------
    // Setters
    //--------------------------------------------

    public function setName(string $name): Earnings
    {
        $this->name = $name;
        return $this;
    }

    public function setAmount(Money $amount): Earnings
    {
        $this->amount = $amount;
        return $this;
    }

    public function increaseAmount(float $n): Earnings
    {
        $this->amount->add($n);
        return $this;
    }

    public function setInflationRate(float $inflationRate): Earnings
    {
        $this->inflationRate = round($inflationRate, 3);
        return $this;
    }

    public function setBeginYear(?int $beginYear): Earnings
    {
        $this->beginYear = $beginYear;
        return $this;
    }

    public function setBeginMonth(?int $beginMonth): Earnings
    {
        $this->beginMonth = $beginMonth;
        return $this;
    }

    public function setEndYear(?int $endYear): Earnings
    {
        $this->endYear = $endYear;
        return $this;
    }

    public function setEndMonth(?int $endMonth): Earnings
    {
        $this->endMonth = $endMonth;
        return $this;
    }

    public function setRepeatEvery(?int $repeatEvery): Earnings
    {
        $this->repeatEvery = $repeatEvery;
        return $this;
    }

    public function markPlanned(): Earnings
    {
        $this->status = self::PLANNED;
        return $this;
    }

    public function markActive(): Earnings
    {
        $this->status = self::ACTIVE;
        return $this;
    }

    public function markEnded(): Earnings
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

}
