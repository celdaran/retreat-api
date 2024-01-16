<?php namespace App\Service\Engine;

/**
 * A class for dealing with money
 * Or, more directly, a class to deal with
 * the issue of floats vs. currency.
 */
class Money {

    protected float $value;

    private \NumberFormatter $fmt;

    //--------------------------------------------
    // Constructor
    //--------------------------------------------

    public function __construct($value = 0.00)
    {
        $this->setValue($value);
        $this->fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
    }

    //--------------------------------------------
    // Public methods
    //--------------------------------------------

    public function assign(float $n)
    {
        $this->setValue($n);
    }

    public function add(float $n)
    {
        $this->setValue($this->value + $this->r($n));
    }

    public function subtract(float $n)
    {
        $this->setValue($this->value - $this->r($n));
    }

    public function value(): float
    {
        return $this->r($this->value);
    }

    public function eq(float $n): bool
    {
        return $this->r($this->value) === $this->r($n);
    }

    public function lt(float $a, float $b): bool
    {
        return $this->r($a) < $this->r($b);
    }

    public function le(float $n): bool
    {
        return $this->r($this->value) <= $this->r($n);
    }

    public function isZero(float $a): bool
    {
        return $this->r($a) === $this->r(0.00);
    }

    //--------------------------------------------
    // Primary setter
    //--------------------------------------------

    private function setValue(float $value)
    {
        $this->value = $this->r($value);
    }

    //--------------------------------------------
    // Value transformers
    //--------------------------------------------

    private function r(float $n): float
    {
        return round($n, 2);
    }

    public function formatted(): string
    {
        return $this->fmt->formatCurrency($this->value, 'USD');
    }

}
