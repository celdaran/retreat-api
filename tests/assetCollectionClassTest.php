<?php

use PHPUnit\Framework\TestCase;

use App\System\Database;
use App\System\Log;
use App\Service\Scenario\AssetCollection;
use App\Service\Engine\IncomeCollection;
use App\Service\Engine\Period;
use App\Service\Engine\Asset;

final class assetCollectionClassTest extends TestCase
{
    private static AssetCollection $assetCollection;
    private static IncomeCollection $incomeCollection;
    private static Database $database;
    private static Log $log;

    public static function setUpBeforeClass(): void
    {
        self::$log = new Log('DEBUG', 'MEMORY');
        self::$database = new Database(self::$log, $_ENV['DBHOST'], $_ENV['DBNAME'], $_ENV['DBUSER'], $_ENV['DBPASS']);
        self::$assetCollection = new AssetCollection(self::$database, self::$log);
        self::$incomeCollection = new IncomeCollection(self::$log);
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
        $period = new Period(2025, 1);
        self::$assetCollection->loadScenario('ut01-assets');
        $balances = self::$assetCollection->getBalances($period, false);
        $this->assertCount(1, $balances);
        $this->assertEquals(1000, $balances['Asset 1']);

        self::$assetCollection->loadScenario('ut02-assets');
        $balances = self::$assetCollection->getBalances($period, false);
        $this->assertCount(2, $balances);
        $this->assertEquals(500, $balances['Asset 1']);
        $this->assertEquals(1000, $balances['Asset 2']);

        self::$assetCollection->loadScenario('ut03-assets');
        $balances = self::$assetCollection->getBalances($period);
        $this->assertCount(3, $balances);
        $this->assertEquals(300, $balances['Asset 1']);
        $this->assertEquals(300, $balances['Asset 2']);
        $this->assertEquals(600, $balances['Asset 3']);

        $balances = self::$assetCollection->getBalances($period, false);
        $this->assertCount(3, $balances);
        $this->assertEquals(300, $balances['Asset 1']);
        $this->assertEquals(300, $balances['Asset 2']);
        $this->assertEquals(600, $balances['Asset 3']);

        $balances = self::$assetCollection->getBalances($period, true);
        $this->assertCount(3, $balances);
        $this->assertEquals('$300', $balances['Asset 1']);
        $this->assertEquals('$300', $balances['Asset 2']);
        $this->assertEquals('$600', $balances['Asset 3']);
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
            $assets = self::$assetCollection->activateAssets($period);

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
            $assets = self::$assetCollection->activateAssets($period);

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
            $assets = self::$assetCollection->activateAssets($period);

            if (($assets[0]->currentBalance() > 100) && $assets[0]->isActive()) {
                $assets[0]->decreaseCurrentBalance(100);
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
            $assets = self::$assetCollection->activateAssets($period);

            if (($assets[0]->currentBalance() >= 100) && $assets[0]->isActive()) {
                $assets[0]->decreaseCurrentBalance(100);
            }

            if (($assets[1]->currentBalance() >= 100) && $assets[1]->isActive()) {
                $assets[1]->decreaseCurrentBalance(100);
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
        $assets = self::$assetCollection->getAssets($period);

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

        $balances = self::$assetCollection->getBalances($period);
        $this->assertEquals(1000, $balances['Asset 1']);
        $this->assertEquals(2000, $balances['Asset 2']);
        $this->assertEquals(3000, $balances['Asset 3']);
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
        self::$assetCollection->earnInterest($period);

        // Check interest calculations successful
        $balances = self::$assetCollection->getBalances($period);
        $this->assertEquals(1001, $balances['Asset 1']);
        $this->assertEquals(2008, $balances['Asset 2']);
        $this->assertEquals(3025, $balances['Asset 3']);

        // Earn interest (again)
        self::$assetCollection->earnInterest($period);

        // Check interest calculations successful
        $balances = self::$assetCollection->getBalances($period);
        $this->assertEquals(1002, $balances['Asset 1']);
        $this->assertEquals(2016, $balances['Asset 2']);
        $this->assertEquals(3050, $balances['Asset 3']);

        // While I'm here, let's sneak in an audit check
        $audit = self::$assetCollection->auditAssets($period);
        $this->assertEquals(1, $audit[0]['period']);
        $this->assertEquals(2025, $audit[0]['year']);
        $this->assertEquals(1, $audit[0]['month']);
        $this->assertEquals('Asset 1', $audit[0]['name']);
        $this->assertEquals(1000, $audit[0]['opening_balance']);
        $this->assertEquals(1002, $audit[0]['current_balance']);
        $this->assertEquals(100, $audit[0]['max_withdrawal']);
        $this->assertEquals('active', $audit[0]['status']);

        $this->assertEquals(1, $audit[2]['period']);
        $this->assertEquals(2025, $audit[2]['year']);
        $this->assertEquals(1, $audit[2]['month']);
        $this->assertEquals('Asset 3', $audit[2]['name']);
        $this->assertEquals(3000, $audit[2]['opening_balance']);
        $this->assertEquals(3050, $audit[2]['current_balance']);
        $this->assertEquals(300, $audit[2]['max_withdrawal']);
        $this->assertEquals('active', $audit[2]['status']);
    }

    /**
     * @throws Exception
     */
    public function testMakeWithdrawals(): void
    {
        self::$assetCollection->loadScenario('ut01-assets');

        $period = new Period(2025, 1);
        $expense = 50;
        $expected = 50;

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(950, $assets[0]->currentBalance());
        $this->assertEquals(50, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(900, $assets[0]->currentBalance());
        $this->assertEquals(100, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(850, $assets[0]->currentBalance());
        $this->assertEquals(150, self::$incomeCollection->value());

        //-------------------------

        self::$incomeCollection->reset();
    }

    /**
     * @throws Exception
     */
    public function testMakeWithdrawals2(): void
    {
        self::$assetCollection->loadScenario('ut02-assets');

        $period = new Period(2025, 1);
        $expense = 100;
        $expected = 100;

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(400, $assets[0]->currentBalance());
        $this->assertEquals(1000, $assets[1]->currentBalance());
        $this->assertEquals(100, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(300, $assets[0]->currentBalance());
        $this->assertEquals(1000, $assets[1]->currentBalance());
        $this->assertEquals(200, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(200, $assets[0]->currentBalance());
        $this->assertEquals(1000, $assets[1]->currentBalance());
        $this->assertEquals(300, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(100, $assets[0]->currentBalance());
        $this->assertEquals(1000, $assets[1]->currentBalance());
        $this->assertEquals(400, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(0, $assets[0]->currentBalance());
        $this->assertEquals(1000, $assets[1]->currentBalance());
        $this->assertEquals(500, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(0, $assets[0]->currentBalance());
        $this->assertEquals(900, $assets[1]->currentBalance());
        $this->assertEquals(600, self::$incomeCollection->value());

        //-------------------------

        self::$incomeCollection->reset();
    }

    /**
     * @throws Exception
     */
    public function testMakeWithdrawals3(): void
    {
        self::$assetCollection->loadScenario('ut03-assets');

        $period = new Period(2025, 1);
        $expense = 500;

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $expected = 100;
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(200, $assets[0]->currentBalance());
        $this->assertEquals(300, $assets[1]->currentBalance());
        $this->assertEquals(600, $assets[2]->currentBalance());
        $this->assertEquals(100, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(100, $assets[0]->currentBalance());
        $this->assertEquals(300, $assets[1]->currentBalance());
        $this->assertEquals(600, $assets[2]->currentBalance());
        $this->assertEquals(200, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        // In the third period, Asset 1 gets drained and Asset 2 kicks in
        // So we pull $200 instead of $100, but that's still shy of the
        // $500 needed for this month
        $expected = 200;
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(  0, $assets[0]->currentBalance());
        $this->assertEquals(200, $assets[1]->currentBalance());
        $this->assertEquals(600, $assets[2]->currentBalance());
        $this->assertEquals(400, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        // In the fourth period, Asset 1 is drained and Asset 2 is still
        // active. But we're back to only getting $100 total for the month
        $expected = 100;
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(  0, $assets[0]->currentBalance());
        $this->assertEquals(100, $assets[1]->currentBalance());
        $this->assertEquals(600, $assets[2]->currentBalance());
        $this->assertEquals(500, self::$incomeCollection->value());

        //-------------------------

        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $period->advance();
        self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $period->advance();
        // Skipping ahead to the ninth period...
        $expected = 100;
        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);
        $lastLog = $this->getLastLog();
        $this->assertStringContainsString("Insufficient funds", $lastLog);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals(   0, $assets[0]->currentBalance());
        $this->assertEquals(   0, $assets[1]->currentBalance());
        $this->assertEquals( 100, $assets[2]->currentBalance());
        $this->assertEquals(1100, self::$incomeCollection->value());

        //-------------------------

        self::$incomeCollection->reset();
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
        $expense = 500;
        $expected = 500;

        self::$assetCollection->activateAssets($period);

        //-------------------------

        $actual = self::$assetCollection->makeWithdrawals($period, $expense, self::$incomeCollection);
        $this->assertEquals($expected, $actual);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals( 900, $assets[0]->currentBalance());
        $this->assertEquals(1800, $assets[1]->currentBalance());
        $this->assertEquals(2800, $assets[2]->currentBalance());
        $this->assertEquals( 500, self::$incomeCollection->value());

        self::$assetCollection->earnInterest($period);

        /** @var Asset[] $assets */
        $assets = self::$assetCollection->getAssets($period);

        $this->assertEquals( 901, $assets[0]->currentBalance());
        $this->assertEquals(1807, $assets[1]->currentBalance());
        $this->assertEquals(2823, $assets[2]->currentBalance());

        //-------------------------

        self::$incomeCollection->reset();
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
