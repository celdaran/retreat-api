<?php

use PHPUnit\Framework\TestCase;

use App\Service\Scenario\AssetCollection;
use App\Service\Engine\Period;
use App\Service\Engine\Asset;
use App\Service\Engine\Money;
use App\System\Database;
use App\System\Log;

final class assetCollectionClassTest extends TestCase
{
    private static AssetCollection $assetCollection;
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
        self::$assetCollection = new AssetCollection(self::$database, self::$log);
    }

    /**
     * @throws Exception
     */
    public function testCount(): void
    {
        self::$assetCollection->loadScenario('no-such-scenario');
        $count = self::$assetCollection->count();
        $this->assertEquals(0, $count);

        self::$assetCollection->loadScenario('ut01-assets');
        $count = self::$assetCollection->count();
        $this->assertEquals(1, $count);

        self::$assetCollection->loadScenario('ut02-assets');
        $count = self::$assetCollection->count();
        $this->assertEquals(2, $count);

        self::$assetCollection->loadScenario('ut03-assets');
        $count = self::$assetCollection->count();
        $this->assertEquals(3, $count);
    }

    /**
     * @throws Exception
     */
    public function testGetBalances(): void
    {
        self::$assetCollection->loadScenario('ut01-assets');
        $balances = self::$assetCollection->getBalances(false);
        $this->assertCount(1, $balances);
        $this->assertEquals(1000.00, $balances['Asset 1']);

        self::$assetCollection->loadScenario('ut02-assets');
        $balances = self::$assetCollection->getBalances(false);
        $this->assertCount(2, $balances);
        $this->assertEquals(500.00, $balances['Asset 1']);
        $this->assertEquals(1000.00, $balances['Asset 2']);

        self::$assetCollection->loadScenario('ut03-assets');
        $balances = self::$assetCollection->getBalances();
        $this->assertCount(3, $balances);
        $this->assertEquals(300.00, $balances['Asset 1']);
        $this->assertEquals(300.00, $balances['Asset 2']);
        $this->assertEquals(600.00, $balances['Asset 3']);

        $balances = self::$assetCollection->getBalances(false);
        $this->assertCount(3, $balances);
        $this->assertEquals(300.00, $balances['Asset 1']);
        $this->assertEquals(300.00, $balances['Asset 2']);
        $this->assertEquals(600.00, $balances['Asset 3']);

        $balances = self::$assetCollection->getBalances(true);
        $this->assertCount(3, $balances);
        $this->assertEquals('$300.00', $balances['Asset 1']);
        $this->assertEquals('$300.00', $balances['Asset 2']);
        $this->assertEquals('$600.00', $balances['Asset 3']);
    }

    /**
     * @throws Exception
     */
    public function testActivateAssets1(): void
    {
        self::$assetCollection->loadScenario('ut01-assets');
        $period = new Period(2024, 1);

        for ($i = 0; $i < 36; $i++) {
            // Activate assets for current period
            self::$assetCollection->activateAssets($period);
            /** @var Asset[] $assets */
            $assets = self::$assetCollection->getAssets();

            // Test that it becomes active at the right time
            if (($period->getYear() >= 2025) && ($period->getMonth() >= 1)) {
                $this->assertEquals(true, $assets[0]->isActive());
                $this->assertEquals(false, $assets[0]->isUntapped());
            } else {
                $this->assertEquals(false, $assets[0]->isActive());
                $this->assertEquals(true, $assets[0]->isUntapped());
            }

            $period->advance();
        }
    }

    /**
     * @throws Exception
     */
    public function testActivateAssets2(): void
    {
        self::$assetCollection->loadScenario('ut02-assets');
        $period = new Period(2024, 1);

        for ($i = 0; $i < 36; $i++) {
            // Activate assets for current period
            self::$assetCollection->activateAssets($period);
            /** @var Asset[] $assets */
            $assets = self::$assetCollection->getAssets();

            // Test that it never becomes active. Because asset #2
            // is scheduled for activation only when asset #1 depletes,
            // and we're not depleting asset #1 (yet)
            $this->assertEquals(false, $assets[1]->isActive());
            $this->assertEquals(true, $assets[1]->isUntapped());

            $period->advance();
        }
    }

    /**
     * @throws Exception
     */
    public function testActivateAssets2b(): void
    {
        self::$assetCollection->loadScenario('ut02-assets');
        $period = new Period(2024, 1);

        for ($i = 0; $i < 24; $i++) {
            // Activate assets for current period
            self::$assetCollection->activateAssets($period);
            /** @var Asset[] $assets */
            $assets = self::$assetCollection->getAssets();

            if ($assets[0]->currentBalance()->ge(100.00) && $assets[0]->isActive()) {
                $assets[0]->decreaseCurrentBalance(100.00);
            }

            if (($period->getYear() >= 2025) && ($period->getMonth() >= 6)) {
                $this->assertEquals(true, $assets[1]->isActive(), $assets[1]->name() . " is active in loop $i");
            } else {
                $this->assertEquals(true, $assets[1]->isUntapped(), $assets[1]->name() . " is untapped in loop $i");
            }

            $period->advance();
        }
    }

    /**
     * @throws Exception
     */
    public function testActivateAssets3(): void
    {
        self::$assetCollection->loadScenario('ut03-assets');
        $period = new Period(2024, 1);

        for ($i = 0; $i < 24; $i++) {
            // Activate assets for current period
            self::$assetCollection->activateAssets($period);
            /** @var Asset[] $assets */
            $assets = self::$assetCollection->getAssets();

            if ($assets[0]->currentBalance()->ge(100.00) && ($assets[0]->isActive())) {
                $assets[0]->decreaseCurrentBalance(100.00);
            }

            if ($assets[1]->currentBalance()->ge(100.00) && ($assets[1]->isActive())) {
                $assets[1]->decreaseCurrentBalance(100.00);
            }

            if (($period->getYear() >= 2025) && ($period->getMonth() >= 7)) {
                $this->assertEquals(true, $assets[2]->isActive(), $assets[2]->name() . " is active in loop $i");
            } else {
                $this->assertEquals(true, $assets[2]->isUntapped(), $assets[2]->name() . " is untapped in loop $i");
            }

            $period->advance();
        }
    }

    /**
     * @throws Exception
     */
    public function testActivateAssets4(): void
    {
        self::$assetCollection->loadScenario('ut04-assets');
        $period = new Period(2025, 1);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        // Did we get them all?
        $this->assertCount(3, $assets);

        // Are they all untapped by default?
        foreach ($assets as $asset) {
            $this->assertEquals(true, $asset->isUntapped());
        }

        // Activate assets for current period
        self::$assetCollection->activateAssets($period);

        // Ensure they're all active
        foreach ($assets as $asset) {
            $this->assertEquals(false, $asset->isUntapped());
        }

        $balances = self::$assetCollection->getBalances();
        $this->assertEquals(1000.00, $balances['Asset 1']);
        $this->assertEquals(2000.00, $balances['Asset 2']);
        $this->assertEquals(3000.00, $balances['Asset 3']);
    }

    /**
     * @throws Exception
     */
    public function testEarnInterest(): void
    {
        self::$assetCollection->loadScenario('ut04-assets');
        $period = new Period(2025, 1);

        // Activate assets for current period
        self::$assetCollection->activateAssets($period);

        // Earn interest
        self::$assetCollection->earnInterest();

        // Check interest calculations successful
        $balances = self::$assetCollection->getBalances();
        $this->assertEquals(1001.67, $balances['Asset 1']);
        $this->assertEquals(2008.33, $balances['Asset 2']);
        $this->assertEquals(3025.00, $balances['Asset 3']);

        // Earn interest (again)
        self::$assetCollection->earnInterest();

        // Check interest calculations successful
        $balances = self::$assetCollection->getBalances();
        $this->assertEquals(1003.34, $balances['Asset 1']);
        $this->assertEquals(2016.70, $balances['Asset 2']);
        $this->assertEquals(3050.21, $balances['Asset 3']);

        // While I'm here, let's sneak in an audit check
        $audit = self::$assetCollection->auditAssets($period);
        $this->assertEquals(1, $audit[0]['period']);
        $this->assertEquals(2025, $audit[0]['year']);
        $this->assertEquals(1, $audit[0]['month']);
        $this->assertEquals('Asset 1', $audit[0]['name']);
        $this->assertEquals(1000.00, $audit[0]['opening_balance']);
        $this->assertEquals(1003.34, $audit[0]['current_balance']);
        $this->assertEquals(100.00, $audit[0]['max_withdrawal']);
        $this->assertEquals('active', $audit[0]['status']);

        $this->assertEquals(1, $audit[2]['period']);
        $this->assertEquals(2025, $audit[2]['year']);
        $this->assertEquals(1, $audit[2]['month']);
        $this->assertEquals('Asset 3', $audit[2]['name']);
        $this->assertEquals(3000.00, $audit[2]['opening_balance']);
        $this->assertEquals(3050.21, $audit[2]['current_balance']);
        $this->assertEquals(300.00, $audit[2]['max_withdrawal']);
        $this->assertEquals('active', $audit[2]['status']);
    }

    /**
     * @throws Exception
     */
    public function testMakeWithdrawals(): void
    {
        self::$assetCollection->loadScenario('ut01-assets');

        $period = new Period(2025, 1);
        $expense = new Money(50.00);
        $expected = new Money(50.00);
        $agi = new Money(0.00);

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(950.00, $assets[0]->currentBalance()->value());
        // TODO: add $taxable flag to assets (e.g., Roth IRAs are not taxable)
        $this->assertEquals(50.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(900.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(100.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(850.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(150.00, $agi->value());
    }

    /**
     * @throws Exception
     */
    public function testMakeWithdrawals2(): void
    {
        self::$assetCollection->loadScenario('ut02-assets');

        $period = new Period(2025, 1);
        $expense = new Money(100.00);
        $expected = new Money(100.00);
        $agi = new Money(0.00);

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(400.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(1000.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(100.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(300.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(1000.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(200.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(200.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(1000.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(300.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(100.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(1000.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(400.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(0.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(1000.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(500.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(0.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(900.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(600.00, $agi->value());
    }

    /**
     * @throws Exception
     */
    public function testMakeWithdrawals3(): void
    {
        self::$assetCollection->loadScenario('ut03-assets');

        $period = new Period(2025, 1);
        $expense = new Money(500.00);
        $agi = new Money(0.00);

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $expected = new Money(100.00);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(200.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(300.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(600.00, $assets[2]->currentBalance()->value());
        $this->assertEquals(100.00, $agi->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(100.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(300.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(600.00, $assets[2]->currentBalance()->value());
        $this->assertEquals(200.00, $agi->value());

        //-------------------------

        $period->advance();
        // In the third period, Asset 1 gets drained and Asset 2 kicks in
        // So we pull $200 instead of $100, but that's still shy of the
        // $500 needed for this month
        $expected->assign(200.00);
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(  0.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(200.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(600.00, $assets[2]->currentBalance()->value());
        $this->assertEquals(400.00, $agi->value());

        //-------------------------

        $period->advance();
        // In the fourth period, Asset 1 is drained and Asset 2 is still
        // active. But we're back to only getting $100 total for the month
        $expected->assign(100.00);
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(  0.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(100.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(600.00, $assets[2]->currentBalance()->value());
        $this->assertEquals(500.00, $agi->value());

        //-------------------------

        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $period->advance();
        // Skipping ahead to the ninth period...
        $expected->assign(100.00);
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals(   0.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(   0.00, $assets[1]->currentBalance()->value());
        $this->assertEquals( 100.00, $assets[2]->currentBalance()->value());
        $this->assertEquals(1100.00, $agi->value());
    }

    /**
     * This is the first "realistic" test: $500 a month
     * pulling from plenty of asset sources simultaneously
     * @throws Exception
     */
    public function testMakeWithdrawals4(): void
    {
        self::$assetCollection->loadScenario('ut04-assets');

        $period = new Period(2025, 1);
        $expense = new Money(500.00);
        $expected = new Money(500.00);
        $agi = new Money(0.00);

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, $agi);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals( 900.00, $assets[0]->currentBalance()->value());
        $this->assertEquals(1800.00, $assets[1]->currentBalance()->value());
        $this->assertEquals(2800.00, $assets[2]->currentBalance()->value());
        $this->assertEquals( 500.00, $agi->value());

        self::$assetCollection->earnInterest();

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets();

        $this->assertEquals( 901.50, $assets[0]->currentBalance()->value());
        $this->assertEquals(1807.50, $assets[1]->currentBalance()->value());
        $this->assertEquals(2823.33, $assets[2]->currentBalance()->value());
    }

    /**
     * @return string
     */
    private function getLastLog(): string
    {
        $logs = self::$assetCollection->getLog()->getLogs();
        return $logs[count($logs) - 1];
    }

}
