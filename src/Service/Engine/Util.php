<?php namespace App\Service\Engine;

class Util
{

    /**
     * Calculate compound interest
     * @return Money
     */
    public static function calculateInterest(float $p, float $r): Money
    {
        // Convert rate
        $r = $r / 100;

        // Set time
        $t = 1 / 12;

        // Calculate new value
        $v = $p * (1 + $r / 12) ** (12 * $t);

        // Return *just* the interest
        return new Money(round($v - $p, 2));
    }

    /**
     * Compare two periods, represented by year and month scalar pairs
     *
     * Modeled after strcmp():
     * "Returns -1 if string1 is less than string2; 1 if string1 is greater than string2, and 0 if they are equal"
     * Meaning: returns -1 if period1 is less than period2; 1 if period1 is greater than period2, and 0 if equal
     *
     * @param int $year1
     * @param int $month1
     * @param ?int $year2
     * @param ?int $month2
     * @return int
     */
    public static function periodCompare(int $year1, int $month1, ?int $year2, ?int $month2): int
    {
        // Explicitly set nulls to linux epoch start
        if ($year2 === null) {
            $year2 = 9999;
        }
        if ($month2 === null) {
            $month2 = 12;
        }

        // Convert to things
        $time1 = strtotime(sprintf('%04d-%02d-01', $year1, $month1));
        $time2 = strtotime(sprintf('%04d-%02d-01', $year2, $month2));

        if ($time1 === $time2) {
            return 0;
        } elseif ($time1 < $time2) {
            return -1;
        } else {
            return 1;
        }
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
    public static function calculateIncomeTax(float $income, int $year): float
    {
        // Base tax brackets and rates for 2024, married filing jointly
        // NOTE: min is calculated after inflation estimates
        $taxBrackets = [
            ['rate' => 0.10, 'min' => 0, 'max' =>  23200],
            ['rate' => 0.12, 'min' => 0, 'max' =>  94300],
            ['rate' => 0.22, 'min' => 0, 'max' => 201050],
            ['rate' => 0.24, 'min' => 0, 'max' => 383900],
            ['rate' => 0.32, 'min' => 0, 'max' => 487450],
            ['rate' => 0.35, 'min' => 0, 'max' => 731200],
            ['rate' => 0.37, 'min' => 0, 'max' => PHP_INT_MAX]
        ];
        $standardDeduction = 29200;

        // Apply inflation at a guess of 2% per year
        // This applies to BOTH tax brackets and the standard deduction
        for ($j = 2024; $j < $year; $j++) {
            $i = 0;
            foreach ($taxBrackets as $bracket) {
                $taxBrackets[$i] = [
                    'rate' => $bracket['rate'],
                    'min' => $bracket['min'],
                    'max' => (int)(round($bracket['max'] * 1.02)),
                ];
                $i++;
            }
            $standardDeduction = (int)(round($standardDeduction * 1.02));
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
