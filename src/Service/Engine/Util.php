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

}
