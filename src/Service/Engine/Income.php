<?php namespace App\Service\Engine;

/**
 * Income
 *
 * This class is used to track income for the sole purpose
 * of calculating income tax
 */
class Income
{
    // Income types
    // Used for tax simulations
    const NONTAXABLE = 0;
    const WAGE = 1;
    const INTEREST = 2;
    const DIVIDEND = 3;
    const SSA = 4;
    const RETIREMENT = 5;
    const INVESTMENT = 6;

    private string $name;
    private int $amount;
    private int $type;

    public function __construct(string $name, int $amount, int $type = Income::WAGE)
    {
        $this->setName($name);
        $this->setAmount($amount);
        $this->setType($type);
    }

    /**
     * @param string $name
     * @return Income
     */
    public function setName(string $name): Income
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param int $balance
     * @return $this
     */
    public function setAmount(int $balance): Income
    {
        $this->amount = round($balance, 2);
        return $this;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType(int $type): Income
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

}
