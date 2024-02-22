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

    /**
     * @return float
     */
    public function taxableValue(int $year): float
    {
        $grossIncome = $this->sum()->value();
        return round($grossIncome - $this->getStandardDeduction($year), 2);
    }

    public function reset()
    {
        unset($this->incomes);
        $this->incomes = [];
    }

    /**
     * @param Period $currentPeriod
     * @param ?int $taxEngine
     * @return float
     * @throws Exception
     */
    public function payIncomeTax(Period $currentPeriod, ?int $taxEngine): float
    {
        $incomeTax = 0.00;
        $cumulativeIncome = $this->value();

        // Spit this out each period, regardless of whether we pay or not
        $this->log->debug("Cumulative income for period {$currentPeriod->getCurrentPeriod()} is $cumulativeIncome");

        // Calculate tax at end of year
        if (($currentPeriod->getCurrentPeriod() % 12 === 0) && ($cumulativeIncome > 0.00)) {
            // Calculate tax (right now everything is using ordinary income; may fix this later)
            $incomeTax = $this->calculateIncomeTax($currentPeriod->getYear(), $taxEngine);

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
     * @param int $year
     * @param ?int $taxEngine
     * @return float
     */
    private function calculateIncomeTax(int $year, ?int $taxEngine): float
    {
        // Initialize tax owed
        $taxOwed = 0.00;

        // Summarize incomes by type
        $incomeSummary = [];
        /** @var Income $income */
        foreach ($this->incomes as $income) {
            if (array_key_exists($income->getType(), $incomeSummary)) {
                $incomeSummary[$income->getType()] += $income->getAmount();
            } else {
                $incomeSummary[$income->getType()] = $income->getAmount();
            }
        }

        if ($taxEngine === 1) {
            // USE THIS PATH TO SIMULATE A GLOBAL/PERPETUAL EFT
            foreach ($incomeSummary as $incomeType => $incomeAmount) {
                $taxOwed += $incomeAmount;
            }
            // TODO: set this percentage somewhere...
            $taxOwed = round($taxOwed * 0.15);
        }

        if ($taxEngine === 2) {
            // Preprocessor
            foreach ($incomeSummary as $incomeType => $incomeAmount) {
                switch ($incomeType) {
                    case Income::SSA:
                        // 85% of SSA benefits are taxable income
                        $incomeSummary[Income::SSA] *= 0.85;
                        break;
                }
            }

            // Final loop
            foreach ($incomeSummary as $incomeType => $incomeAmount) {
                switch ($incomeType) {
                    case Income::NONTAXABLE:
                        $taxOwed += 0.00;
                        break;
                    case Income::WAGE:
                    case Income::INTEREST:
                    case Income::RETIREMENT:
                    case Income::SSA:
                        $taxOwed += $this->calculateOrdinaryIncomeTax($incomeAmount, $year);
                        break;
                    case Income::DIVIDEND:
                        $taxOwed += $this->calculateDividendIncomeTax($incomeAmount, $year);
                        break;
                    case Income::INVESTMENT:
                        $taxOwed += $this->calculateInvestmentIncomeTax($incomeAmount, $year);
                        break;
                }
            }
        }

        return $taxOwed;
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
     * @param float $income
     * @param int $year
     * @return float
     */
    private function calculateOrdinaryIncomeTax(float $income, int $year): float
    {
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

        // Apply inflation at a guess of 1% per year
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
        $taxableIncome = round($income - $this->getStandardDeduction($year), 2);
        if ($taxableIncome < 0) {
            return 0.00;
        }

        // Initialize tax owed
        $taxOwed = 0.00;

        // Calculate tax owed based on income and tax brackets
        foreach ($taxBrackets as $bracket) {
            if ($taxableIncome <= $bracket['max']) {
                $taxOwed += $taxableIncome * $bracket['rate'];
                break;
            } else {
                $taxOwed += ($bracket['max'] - $bracket['min'] + 1) * $bracket['rate'];
                $taxableIncome -= ($bracket['max'] - $bracket['min'] + 1);
            }
        }

        return $taxOwed;
    }

    /**
     * @param float $income
     * @param int $year
     * @return float
     */
    private function calculateDividendIncomeTax(float $income, int $year): float
    {
        // TODO: follow up on this
        if ($this->taxableValue($year) < 89250) {
            return 0.00;
        } else {
            return round($income * 0.15, 2);
        }
    }

    /**
     * @param float $income
     * @param int $year
     * @return float
     */
    private function calculateInvestmentIncomeTax(float $income, int $year): float
    {
        // TODO: This very wrong
        // REVISIT LATER
        return $income * 0.15;
    }

    /**
     * @param int $year
     * @return float
     */
    private function getStandardDeduction(int $year): float
    {
        // begin with 2024 deduction
        $standardDeduction = 29200;

        // Apply inflation at a guess of 1% per year
        for ($j = 2024; $j < $year; $j++) {
            $standardDeduction = (int)(round($standardDeduction * 1.01));
        }

        return $standardDeduction;
    }

}
