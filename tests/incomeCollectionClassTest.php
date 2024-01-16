<?php

use PHPUnit\Framework\TestCase;

use App\Service\Data\IncomeCollection;
use App\Service\Engine\Period;
use App\Service\Engine\Income;

final class incomeCollectionClassTest extends TestCase
{
    private static IncomeCollection $incomeCollection;
    private static array $scenarios;

    public static function setUpBeforeClass(): void
    {
        self::$incomeCollection = new IncomeCollection();

        self::$scenarios = [
            'scenario1' => [
                [
                    'income_name' => 'income number 1',
                    'amount' => 100.00,
                    'inflation_rate' => 0.000,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
                [
                    'income_name' => 'income number 2',
                    'amount' => 50.00,
                    'inflation_rate' => 2.000,
                    'begin_year' => 2026,
                    'begin_month' => 2,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
                [
                    'income_name' => 'income number 3',
                    'amount' => 25.00,
                    'inflation_rate' => 3.000,
                    'begin_year' => 2026,
                    'begin_month' => 3,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
            ],

            'scenario2' => [
                [
                    'income_name' => 'income number 1',
                    'amount' => 100.00,
                    'inflation_rate' => 0.000,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
                [
                    'income_name' => 'income number 2',
                    'amount' => 50.00,
                    'inflation_rate' => 3.000,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
            ]
        ];
    }

    public function testTallyIncomes(): void
    {
        self::$incomeCollection->loadScenarioFromMemory('scenario1', self::$scenarios);

        $period = new Period(2026, 1);
        $incomes = self::$incomeCollection->tallyIncome($period);
        $this->assertEquals(100.00, $incomes->value(), "scenario1, period 1");

        $period->advance();
        $incomes = self::$incomeCollection->tallyIncome($period);
        $this->assertEquals(150.00, $incomes->value(), "scenario1, period 2");

        $period->advance();
        $incomes = self::$incomeCollection->tallyIncome($period);
        $this->assertEquals(175.00, $incomes->value(), "scenario1, period 3");
    }

    public function testApplyInflation(): void
    {
        self::$incomeCollection->loadScenarioFromMemory('scenario1', self::$scenarios);

        self::$incomeCollection->applyInflation();

        $incomes = self::$incomeCollection->getIncome();

        /** @var Income $income1 */
        $income1 = $incomes[0];
        /** @var Income $income2 */
        $income2 = $incomes[1];
        /** @var Income $income3 */
        $income3 = $incomes[2];

        $this->assertEquals(100.00, $income1->amount()->value());
        $this->assertEquals(50.08, $income2->amount()->value());
        $this->assertEquals(25.06, $income3->amount()->value());
    }

}
