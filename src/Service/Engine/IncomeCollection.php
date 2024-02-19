<?php namespace App\Service\Engine;

use Exception;
use App\System\Log;

class IncomeCollection
{
    private Log $log;
    private array $incomes = [];

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    public function add(Income $income)
    {
        $this->incomes[] = $income;
    }

    public function sum(): Money
    {
        $value = new Money();
        /** @var Income $i */
        foreach ($this->incomes as $i) {
            $value->add($i->getAmount());
        }
        return $value;
    }

    /**
     * @return float
     */
    public function value(): float
    {
        return $this->sum()->value();
    }

    public function reset()
    {
        unset($this->incomes);
        $this->incomes = [];
    }

    /**
     * @return float
     * @throws Exception
     */
    public function payIncomeTax(Period $currentPeriod): float
    {
        $incomeTax = 0.00;
        $cumulativeIncome = $this->value();

        // Spit this out each period, regardless of whether we pay or not
        $this->log->debug("Cumulative income for period {$currentPeriod->getCurrentPeriod()} is $cumulativeIncome");

        // Calculate tax at end of year
        if (($currentPeriod->getCurrentPeriod() % 12 === 0) && ($cumulativeIncome > 0.00)) {
            // Calculate tax (right now everything is using ordinary income; may fix this later)
            $incomeTax = $this->calculateIncomeTax($currentPeriod->getYear());

            // Log our ETR just as an FYI: it's not required by the engine
            $effectiveTaxRate = ($incomeTax / $cumulativeIncome) * 100;
            $msg = sprintf("Paying income tax of %0.2f in period %d (effective tax rate: %0.3f%%)",
                $incomeTax,
                $currentPeriod->getCurrentPeriod(),
                $effectiveTaxRate
            );
            $this->log->debug($msg);

            // Zero out our annual income for next year
            $this->reset();
        }

        return $incomeTax;
    }

    /**
     * calculateIncomeTax
     *
     * This function takes an income amount and year and returns
     * the tax amount owed. This then becomes an expense for that
     * month, requiring us to pull from assets or income in order
     * to cover the amount owed.
     *
     * Note: The year is used to simulate inflation. The base
     * tax rate is taking from 2024 IRS brackets. The inflation
     * amount is a simple 2.000% for now. I may enhance this.
     *
     * Note: This assumes income is already AGI but at this point
     * in development, my caller provides a non-adjusted amount.
     *
     * Note: This assumes standard deduction.
     *
     * @param int $year
     * @return float
     */
    private function calculateIncomeTax(int $year): float
    {
        // TODO: Take Advantage of the IncomeCollection
        // TODO: Take Advantage of IncomeTypeId
        // TODO: Make this as real as it's gonna get

        // Return the current collection value
        $income = $this->value();

        // Base tax brackets and rates for 2024, married filing jointly
        // NOTE: min is calculated after inflation estimates
        $taxBrackets = [
            ['rate' => 0.10, 'min' => 0, 'max' => 23200],
            ['rate' => 0.12, 'min' => 0, 'max' => 94300],
            ['rate' => 0.22, 'min' => 0, 'max' => 201050],
            ['rate' => 0.24, 'min' => 0, 'max' => 383900],
            ['rate' => 0.32, 'min' => 0, 'max' => 487450],
            ['rate' => 0.35, 'min' => 0, 'max' => 731200],
            ['rate' => 0.37, 'min' => 0, 'max' => PHP_INT_MAX]
        ];
        $standardDeduction = 29200;

        // Apply inflation at a guess of 1% per year
        // This applies to BOTH tax brackets and the standard deduction
        for ($j = 2024; $j < $year; $j++) {
            $i = 0;
            foreach ($taxBrackets as $bracket) {
                $taxBrackets[$i] = [
                    'rate' => $bracket['rate'],
                    'min' => $bracket['min'],
                    'max' => (int)(round($bracket['max'] * 1.01)),
                ];
                $i++;
            }
            $standardDeduction = (int)(round($standardDeduction * 1.01));
        }

        // Set minimums
        $i = 0;
        $previousMax = -1;
        foreach ($taxBrackets as $bracket) {
            $taxBrackets[$i]['min'] = $previousMax + 1;
            $previousMax = $bracket['max'];
            $i++;
        }

        // Apply deduction
        $income -= $standardDeduction;
        if ($income < 0) {
            return 0.00;
        }

        // Initialize tax owed
        $taxOwed = 0.00;

        // Calculate tax owed based on income and tax brackets
        foreach ($taxBrackets as $bracket) {
            if ($income <= $bracket['max']) {
                $taxOwed += $income * $bracket['rate'];
                break;
            } else {
                $taxOwed += ($bracket['max'] - $bracket['min'] + 1) * $bracket['rate'];
                $income -= ($bracket['max'] - $bracket['min'] + 1);
            }
        }

        return $taxOwed;
    }
}
