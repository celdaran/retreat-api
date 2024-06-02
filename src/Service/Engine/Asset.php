<?php namespace App\Service\Engine;

use PHPUnit\Util\Exception;

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
    protected int $openingBalance;
    protected int $currentBalance;
    protected int $maxWithdrawal;
    protected float $apr;
    protected int $incomeType;
    protected ?int $beginAfter;
    protected ?int $beginYear;
    protected ?int $beginMonth;
    protected ?int $ignoreUntilYear;
    protected ?int $ignoreUntilMonth;
    protected int $status;

    public function __construct()
    {
        $this->status = self::UNTAPPED;
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

    public function setOpeningBalance(int $openingBalance): Asset
    {
        $this->openingBalance = $openingBalance;
        return $this;
    }

    public function setCurrentBalance(int $currentBalance): Asset
    {
        $this->currentBalance = $currentBalance;
        return $this;
    }

    public function increaseCurrentBalance(int $amount): Asset
    {
        $this->currentBalance += $amount;
        return $this;
    }

    public function decreaseCurrentBalance(int $amount): Asset
    {
        // Cannot decrease balance of a non-active asset
        if (!$this->isActive()) {
            throw new Exception("decreaseCurrentBalance: asset is not active");
        }

        // Subtract
        $this->currentBalance -= $amount;
        if ($this->currentBalance() === 0) {
            $this->markDepleted();
        } elseif ($this->currentBalance() < 0) {
            throw new Exception("decreaseCurrentBalance: asset balance is negative");
        }

        return $this;
    }

    public function setMaxWithdrawal(int $maxWithdrawal): Asset
    {
        $this->maxWithdrawal = $maxWithdrawal;
        return $this;
    }

    public function setApr(float $apr): Asset
    {
        $this->apr = round($apr, 3);
        return $this;
    }

    public function setIncomeType(int $incomeType): Asset
    {
        $this->incomeType = $incomeType;
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

    public function setIgnoreUntilYear(?int $ignoreUntilYear): Asset
    {
        $this->ignoreUntilYear = $ignoreUntilYear;
        return $this;
    }

    public function setIgnoreUntilMonth(?int $ignoreUntilMonth): Asset
    {
        $this->ignoreUntilMonth = $ignoreUntilMonth;
        return $this;
    }

    public function markUntapped(): Asset
    {
        $this->status = self::UNTAPPED;
        return $this;
    }

    public function markActive(): Asset
    {
        $this->status = self::ACTIVE;
        return $this;
    }

    public function markDepleted(): Asset
    {
        $this->status = self::DEPLETED;
        $this->currentBalance = 0;
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

    public function openingBalance(): int
    {
        return $this->openingBalance;
    }

    public function currentBalance(): int
    {
        return $this->currentBalance;
    }

    public function maxWithdrawal(): int
    {
        return $this->maxWithdrawal;
    }

    public function apr(): float
    {
        return $this->apr;
    }

    public function incomeType(): int
    {
        return $this->incomeType;
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

    public function ignoreUntilYear(): ?int
    {
        return $this->ignoreUntilYear;
    }

    public function ignoreUntilMonth(): ?int
    {
        return $this->ignoreUntilMonth;
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

    /**
     * Activate an asset
     * @param Period $period
     * @param ?Asset $beginAfterAsset
     */
    public function activate(Period $period, ?Asset $beginAfterAsset = null)
    {
        if ($this->isUntapped()) {
            if ($beginAfterAsset !== null) {
                if ($beginAfterAsset->isDepleted()) {
                    /*
                    $msg = sprintf('Activating asset "%s", in %4d-%02d, after previous asset depleted',
                        $this->name(),
                        $period->getYear(),
                        $period->getMonth(),
                    );
                    $this->getLog()->debug($msg);
                    */
                    $this->markActive();
                }

            } else {
                if ($this->timeToActivate($period)) {
                    /*
                    $msg = sprintf('Activating asset "%s", in %4d-%02d, as planned from the start',
                        $this->name(),
                        $period->getYear(),
                        $period->getMonth(),
                    );
                    $this->getLog()->debug($msg);
                    */
                    $this->markActive();
                }
            }
        }
    }

    public function canEarnInterest(): bool
    {
        if (!$this->isDepleted()) {
            if ($this->currentBalance() > 0.00) {
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

    public function isIgnored(Period $period): bool
    {
        // If ignore not set at all, then don't ignore
        if (($this->ignoreUntilYear() === null) || ($this->ignoreUntilMonth() === null)) {
            return false;
        }

        // Otherwise, compare it
        $cmp = Util::periodCompare(
            $this->ignoreUntilYear(), $this->ignoreUntilMonth(),
            $period->getYear(), $period->getMonth(),
        );

        if ($cmp >= 0) {
            return true;
        }

        return false;
    }

}
