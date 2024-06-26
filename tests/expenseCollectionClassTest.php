<?php

use PHPUnit\Framework\TestCase;

use App\Service\Scenario\ExpenseCollection;
use App\Service\Engine\Period;
use App\Service\Engine\Expense;
use App\System\Database;
use App\System\Log;

final class expenseCollectionClassTest extends TestCase
{
    private static ExpenseCollection $expenseCollection;
    private static Database $database;
    private static Log $log;

    public function __construct()
    {
        self::$log = new Log('DEBUG', 'MEMORY');
        self::$database = new Database(self::$log, $_ENV['DBHOST'], $_ENV['DBNAME'], $_ENV['DBUSER'], $_ENV['DBPASS']);
        parent::__construct();
    }

    public static function setUpBeforeClass(): void
    {
        self::$expenseCollection = new ExpenseCollection(self::$database, self::$log);
    }

    /**
     * @throws Exception
     */
    public function testCountNoExpenses(): void
    {
        $this->expectException(Exception::class);
        self::$expenseCollection->loadScenario('no-such-scenario');
    }

    /**
     * @throws Exception
     */
    public function testCount(): void
    {
        self::$expenseCollection->loadScenario('ut01-expenses');
        $count = self::$expenseCollection->count();
        $this->assertEquals(1, $count);

        self::$expenseCollection->loadScenario('ut02-expenses');
        $count = self::$expenseCollection->count();
        $this->assertEquals(1, $count);

        self::$expenseCollection->loadScenario('ut03-expenses');
        $count = self::$expenseCollection->count();
        $this->assertEquals(1, $count);

        self::$expenseCollection->loadScenario('ut04-expenses');
        $count = self::$expenseCollection->count();
        $this->assertEquals(3, $count);
    }

    /**
     * @throws Exception
     */
    public function testInactiveAmounts(): void
    {
        self::$expenseCollection->loadScenario('ut04-expenses');
        $amounts = self::$expenseCollection->getAmounts();
        $this->assertNull($amounts['Expense 1']);
        $this->assertNull($amounts['Expense 2']);
        $this->assertNull($amounts['Expense 3']);
    }

    /**
     * @throws Exception
     */
    public function testActiveAmounts(): void
    {
        self::$expenseCollection->loadScenario('ut04-expenses');
        /** @var Expense[] $expenses */
        $expenses = self::$expenseCollection->getExpenses();

        $expenses[0]->markActive();
        $amounts = self::$expenseCollection->getAmounts();
        $this->assertEquals(500, $amounts['Expense 1']);
        $this->assertNull($amounts['Expense 2']);
        $this->assertNull($amounts['Expense 3']);

        $expenses[1]->markActive();
        $amounts = self::$expenseCollection->getAmounts();
        $this->assertEquals(500, $amounts['Expense 1']);
        $this->assertEquals(600, $amounts['Expense 2']);
        $this->assertNull($amounts['Expense 3']);

        $expenses[2]->markActive();
        $amounts = self::$expenseCollection->getAmounts();
        $this->assertEquals(500, $amounts['Expense 1']);
        $this->assertEquals(600, $amounts['Expense 2']);
        $this->assertEquals(700, $amounts['Expense 3']);
    }

    /**
     * @throws Exception
     */
    public function testTallyExpenses(): void
    {
        self::$expenseCollection->loadScenario('ut04-expenses');
        $period = new Period(2025, 1);

        /** @var Expense[] $expenses */
        $expenses = self::$expenseCollection->getExpenses();

        $expenses[0]->markActive();
        $expected = 500;
        $actual = self::$expenseCollection->tallyExpenses($period);
        $this->assertEquals($expected, $actual);

        $expenses[1]->markActive();
        $expected = 1100;
        $actual = self::$expenseCollection->tallyExpenses($period);
        $this->assertEquals($expected, $actual);

        $expenses[2]->markActive();
        $expected = 1800;
        $actual = self::$expenseCollection->tallyExpenses($period);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws Exception
     */
    public function testApplyInflation(): void
    {
        self::$expenseCollection->loadScenario('ut04-expenses');

        /** @var Expense[] $expenses */
        $expenses = self::$expenseCollection->getExpenses();
        $expenses[0]->markActive();
        $expenses[1]->markActive();
        $expenses[2]->markActive();

        self::$expenseCollection->applyInflation();

        $expected = 500;
        $this->assertEquals($expected, $expenses[0]->amount());
        $expected = 600;
        $this->assertEquals($expected, $expenses[1]->amount());
        $expected = 702;
        $this->assertEquals($expected, $expenses[2]->amount());
    }

    /**
     * @throws Exception
     */
    public function testClone(): void
    {
        self::$expenseCollection->loadScenario('ut04-expenses');

        $scenarioId = self::$expenseCollection->id();
        self::$expenseCollection->clone($scenarioId, 'ut04-expenses-clone', 'ut04-expenses-clone unit test', 1);

        self::$expenseCollection->loadScenario('ut04-expenses-clone');

        $expenses = self::$expenseCollection->getExpenses();

        $expected = 500;
        $this->assertEquals($expected, $expenses[0]->amount());
        $expected = 600;
        $this->assertEquals($expected, $expenses[1]->amount());
        $expected = 700;
        $this->assertEquals($expected, $expenses[2]->amount());

        self::$expenseCollection->delete();
    }

}
