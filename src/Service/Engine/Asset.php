<?php namespace App\Service\Engine;

/**
 * A class representing an asset
 */
class Asset
{

    const UNTAPPED = 0;
    const ACTIVE = 1;
    const DEPLETED = 9;

    protected int $id;
    protected string $name;
    protected Money $openingBalance;
    protected Money $currentBalance;
    protected Money $maxWithdrawal;
    protected float $apr;
    protected ?int $beginAfter;
    protected ?int $beginYear;
    protected ?int $beginMonth;
    protected int $status;

    public function __construct()
    {
    }

    //--------------------------------------------
    // Setters
    //--------------------------------------------

    public function setId(int $id): Asset
    {
        $this->id = $id;
        return $this;
    }

    public function setName(string $name): Asset
    {
        $this->name = $name;
        return $this;
    }

    public function setOpeningBalance(Money $openingBalance): Asset
    {
        $this->openingBalance = $openingBalance;
        return $this;
    }

    public function setCurrentBalance(Money $currentBalance): Asset
    {
        $this->currentBalance = $currentBalance;
        return $this;
    }

    public function increaseCurrentBalance(float $amount): Asset
    {
        $this->currentBalance->add($amount);
        return $this;
    }

    public function setMaxWithdrawal(Money $maxWithdrawal): Asset
    {
        $this->maxWithdrawal = $maxWithdrawal;
        return $this;
    }

    public function setApr(float $apr): Asset
    {
        $this->apr = round($apr, 3);
        return $this;
    }

    public function setBeginAfter(?int $beginAfter): Asset
    {
        $this->beginAfter = $beginAfter;
        return $this;
    }

    public function setBeginYear(?int $beginYear): Asset
    {
        $this->beginYear = $beginYear;
        return $this;
    }

    public function setBeginMonth(?int $beginMonth): Asset
    {
        $this->beginMonth = $beginMonth;
        return $this;
    }

    public function markUntapped(): Asset
    {
        $this->status = self::UNTAPPED;
        return $this;
    }

    /* potential replacement for above */
    public function markActive(): Asset
    {
        $this->status = self::ACTIVE;
        return $this;
    }

    /* potential replacement for above */
    public function markDepleted(): Asset
    {
        $this->status = self::DEPLETED;
        $this->currentBalance = new Money();
        return $this;
    }

    //--------------------------------------------
    // Getters
    //--------------------------------------------

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function openingBalance(): Money
    {
        return $this->openingBalance;
    }

    public function currentBalance(): Money
    {
        return $this->currentBalance;
    }

    public function maxWithdrawal(): Money
    {
        return $this->maxWithdrawal;
    }

    public function apr(): float
    {
        return $this->apr;
    }

    public function beginAfter(): ?int
    {
        return $this->beginAfter;
    }

    public function beginYear(): ?int
    {
        return $this->beginYear;
    }

    public function beginMonth(): ?int
    {
        return $this->beginMonth;
    }

    public function isUntapped(): bool
    {
        return $this->status === self::UNTAPPED;
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function isDepleted(): bool
    {
        return $this->status === self::DEPLETED;
    }

    public function status(): string
    {
        return match ($this->status) {
            self::UNTAPPED => 'untapped',
            self::ACTIVE => 'active',
            self::DEPLETED => 'depleted',
            default => 'unknown',
        };
    }

    //--------------------------------------------
    // Logic
    //--------------------------------------------

    public function canEarnInterest(): bool
    {
        if (!$this->isDepleted()) {
            if ($this->currentBalance()->value() > 0.00) {
                return true;
            }
        }
        return false;
    }

    public function timeToActivate(Period $period): bool
    {
        if ($this->isUntapped()) {

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

}
