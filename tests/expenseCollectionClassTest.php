<?php

use PHPUnit\Framework\TestCase;

use App\Service\Data\ExpenseCollection;
use App\Service\Engine\Period;
use App\Service\Engine\Expense;

final class expenseCollectionClassTest extends TestCase
{
    private static ExpenseCollection $expenseCollection;
    private static array $scenarios;

    public static function setUpBeforeClass(): void
    {
        self::$expenseCollection = new ExpenseCollection();

        self::$scenarios = [
            'scenario1' => [
                [
                    'expense_name' => 'expense number 1',
                    'amount' => 100.00,
                    'inflation_rate' => 0.000,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
                [
                    'expense_name' => 'expense number 2',
                    'amount' => 50.00,
                    'inflation_rate' => 2.000,
                    'begin_year' => 2026,
                    'begin_month' => 2,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
                [
                    'expense_name' => 'expense number 3',
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
                    'expense_name' => 'expense number 1',
                    'amount' => 100.00,
                    'inflation_rate' => 0.000,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                    'end_year' => null,
                    'end_month' => null,
                    'repeat_every' => null,
                ],
                [
                    'expense_name' => 'expense number 2',
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

    public function testTallyExpenses(): void
    {
        self::$expenseCollection->loadScenarioFromMemory('scenario1', self::$scenarios);

        $period = new Period(2026, 1);
        $expenses = self::$expenseCollection->tallyExpenses($period);
        $this->assertEquals(100.00, $expenses->value(), "scenario1, period 1");

        $period->advance();
        $expenses = self::$expenseCollection->tallyExpenses($period);
        $this->assertEquals(150.00, $expenses->value(), "scenario1, period 2");

        $period->advance();
        $expenses = self::$expenseCollection->tallyExpenses($period);
        $this->assertEquals(175.00, $expenses->value(), "scenario1, period 3");
    }

    public function testApplyInflation(): void
    {
        self::$expenseCollection->loadScenarioFromMemory('scenario1', self::$scenarios);

        self::$expenseCollection->applyInflation();

        $expenses = self::$expenseCollection->getExpenses();

        /** @var Expense $expense1 */
        $expense1 = $expenses[0];
        /** @var Expense $expense2 */
        $expense2 = $expenses[1];
        /** @var Expense $expense3 */
        $expense3 = $expenses[2];

        $this->assertEquals(100.00, $expense1->amount()->value());
        $this->assertEquals(50.08, $expense2->amount()->value());
        $this->assertEquals(25.06, $expense3->amount()->value());
    }

}
