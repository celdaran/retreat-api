<?php namespace App\Service\Engine;

class SimulationParameters
{
    /** @var string */
    private string $expense;

    /** @var string */
    private string $asset;

    /** @var string */
    private string $earnings;

    /** @var Until */
    private Until $until;

    /** @var ?int */
    private ?int $startYear;

    /** @var ?int */
    private ?int $startMonth;

    /** @var ?int */
    private ?int $taxEngine;

    public function __construct(
        string $expense,
        string $asset,
        string $earnings,
        Until $until,
        ?int $startYear = null,
        ?int $startMonth = null,
        ?int $taxEngine = null)
    {
        $this
            ->setExpense($expense)
            ->setAsset($asset)
            ->setEarnings($earnings)
            ->setUntil($until)
            ->setStartYear($startYear)
            ->setStartMonth($startMonth)
            ->setTaxEngine($taxEngine)
        ;
    }

    /**
     * @param string $expense
     * @return SimulationParameters
     */
    public function setExpense(string $expense): SimulationParameters
    {
        $this->expense = $expense;
        return $this;
    }

    /**
     * @param string $asset
     * @return SimulationParameters
     */
    public function setAsset(string $asset): SimulationParameters
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @param string $earnings
     * @return SimulationParameters
     */
    public function setEarnings(string $earnings): SimulationParameters
    {
        $this->earnings = $earnings;
        return $this;
    }

    /**
     * @param Until $until
     * @return SimulationParameters
     */
    public function setUntil(Until $until): SimulationParameters
    {
        $this->until = $until;
        return $this;
    }

    /**
     * @param ?int $startYear
     * @return SimulationParameters
     */
    public function setStartYear(?int $startYear): SimulationParameters
    {
        $this->startYear = $startYear;
        return $this;
    }

    /**
     * @param ?int $startMonth
     * @return SimulationParameters
     */
    public function setStartMonth(?int $startMonth): SimulationParameters
    {
        $this->startMonth = $startMonth;
        return $this;
    }

    public function setTaxEngine(?int $taxEngine = null): SimulationParameters
    {
        if ($taxEngine === null) {
            // Default to the flat ETR
            $this->taxEngine = 1;
        } else {
            // Otherwise, explicitly set the engine
            $this->taxEngine = $taxEngine;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getExpense(): string
    {
        return $this->expense;
    }

    /**
     * @return string
     */
    public function getAsset(): string
    {
        return $this->asset;
    }

    /**
     * @return string
     */
    public function getEarnings(): string
    {
        return $this->earnings;
    }

    /**
     * @return Until
     */
    public function getUntil(): Until
    {
        return $this->until;
    }

    /**
     * @return ?int
     */
    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    /**
     * @return ?int
     */
    public function getStartMonth(): ?int
    {
        return $this->startMonth;
    }

    /**
     * @return ?int
     */
    public function getTaxEngine(): ?int
    {
        return $this->taxEngine;
    }

}
