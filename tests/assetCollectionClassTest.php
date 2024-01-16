<?php

use PHPUnit\Framework\TestCase;

use App\Service\Data\AssetCollection;
use App\Service\Engine\Period;
use App\Service\Engine\Asset;
use App\Service\Engine\Money;

final class assetCollectionClassTest extends TestCase
{
    private static AssetCollection $assetCollection;
    private static array $scenarios;

    public static function setUpBeforeClass(): void
    {
        self::$assetCollection = new AssetCollection();

        self::$scenarios = [
            'scenario1' => [
                [
                    'asset_id' => 1,
                    'asset_name' => 'asset number 1',
                    'opening_balance' => 1000.00,
                    'max_withdrawal' => 10.00,
                    'apr' => 1.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
                [
                    'asset_id' => 2,
                    'asset_name' => 'asset number 2',
                    'opening_balance' => 2000.00,
                    'max_withdrawal' => 500.00,
                    'apr' => 2.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
                [
                    'asset_id' => 3,
                    'asset_name' => 'asset number 3',
                    'opening_balance' => 750.00,
                    'max_withdrawal' => 75.00,
                    'apr' => 3.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
            ],

            'scenario2' => [
                [
                    'asset_id' => 1,
                    'asset_name' => 'asset number 1',
                    'opening_balance' => 20.00,
                    'max_withdrawal' => 10.00,
                    'apr' => 0.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
                [
                    'asset_id' => 2,
                    'asset_name' => 'asset number 2',
                    'opening_balance' => 50.00,
                    'max_withdrawal' => 50.00,
                    'apr' => 0.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 2,
                ],
                [
                    'asset_id' => 3,
                    'asset_name' => 'asset number 3',
                    'opening_balance' => 10.00,
                    'max_withdrawal' => 10.00,
                    'apr' => 0.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
            ],

            'scenario3' => [
                [
                    'asset_id' => 1,
                    'asset_name' => 'asset number 1',
                    'opening_balance' => 20.00,
                    'max_withdrawal' => 10.00,
                    'apr' => 2.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
                [
                    'asset_id' => 2,
                    'asset_name' => 'asset number 2',
                    'opening_balance' => 50.00,
                    'max_withdrawal' => 50.00,
                    'apr' => 1.500,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 2,
                ],
                [
                    'asset_id' => 3,
                    'asset_name' => 'asset number 3',
                    'opening_balance' => 10.00,
                    'max_withdrawal' => 10.00,
                    'apr' => 0.375,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 1,
                ],
                [
                    'asset_id' => 4,
                    'asset_name' => 'asset number 4',
                    'opening_balance' => 200.00,
                    'max_withdrawal' => 50.00,
                    'apr' => 0.000,
                    'begin_after' => null,
                    'begin_year' => 2026,
                    'begin_month' => 6,
                ],
            ],
        ];
    }

    public function testMakeWithdrawals(): void
    {
        self::$assetCollection->loadScenarioFromMemory('scenario1', self::$scenarios);

        $period = new Period(2026, 1);
        $expense = new Money(50.00);
        $expected = new Money(50.00);

        self::$assetCollection->activateAssets($period);

        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $this->assertEquals($expected, $actual, '$50.00 can be drawn from scenario1');

        $assets = self::$assetCollection->getAssets();

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(990.00, $asset1->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset2 = $assets[1];
        $this->assertEquals(1960.00, $asset2->currentBalance()->value());

        /** @var Asset $asset3 */
        $asset3 = $assets[2];
        $this->assertEquals(750.00, $asset3->currentBalance()->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $this->assertEquals($expected, $actual);

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(980.00, $asset1->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset2 = $assets[1];
        $this->assertEquals(1920.00, $asset2->currentBalance()->value());

        /** @var Asset $asset3 */
        $asset3 = $assets[2];
        $this->assertEquals(750.00, $asset3->currentBalance()->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $this->assertEquals($expected, $actual);

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(970.00, $asset1->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset2 = $assets[1];
        $this->assertEquals(1880.00, $asset2->currentBalance()->value());

        /** @var Asset $asset3 */
        $asset3 = $assets[2];
        $this->assertEquals(750.00, $asset3->currentBalance()->value());
    }

    public function testMakeWithdrawals2(): void
    {
        self::$assetCollection->loadScenarioFromMemory('scenario2', self::$scenarios);

        $period = new Period(2026, 1);
        $expense = new Money(50.00);
        $expected = new Money(20.00);

        self::$assetCollection->activateAssets($period);

        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $this->assertEquals($expected, $actual, '$50.00 is too much for scenario2');

        $assets = self::$assetCollection->getAssets();

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(10.00, $asset1->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset3 = $assets[2];
        $this->assertEquals(0.00, $asset3->currentBalance()->value());

        //-------------------------

        $period->advance();
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $expected = new Money(50.00);
        $this->assertEquals($expected, $actual);

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(0.00, $asset1->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset2 = $assets[1];
        $this->assertEquals(10.00, $asset2->currentBalance()->value());

        /** @var Asset $asset3 */
        $asset3 = $assets[2];
        $this->assertEquals(0.00, $asset3->currentBalance()->value());
    }

    public function testMakeWithdrawals3(): void
    {
        self::$assetCollection->loadScenarioFromMemory('scenario3', self::$scenarios);

        $period = new Period(2026, 1);
        $expense = new Money(50.00);
        $expected = new Money(20.00);

        self::$assetCollection->activateAssets($period);

        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $this->assertEquals($expected, $actual, '$50.00 cannot be drawn for scenario3');

        $assets = self::$assetCollection->getAssets();

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(10.00, $asset1->currentBalance()->value());

        /** @var Asset $asset1 */
        $asset2 = $assets[1];
        $this->assertEquals(50.00, $asset2->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset3 = $assets[2];
        $this->assertEquals(0.00, $asset3->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset4 = $assets[3];
        $this->assertEquals(200.00, $asset4->currentBalance()->value());

        //-------------------------

        $period->advance(); // move to period 2026-02
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $expected = new Money(50.00);
        $this->assertEquals($expected, $actual);

        $period->advance(); // move to period 2026-03
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $expected = new Money(10.00);
        $this->assertEquals($expected, $actual);

        $period->advance(); // move to period 2026-04
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $expected = new Money(0.00);
        $this->assertEquals($expected, $actual);

        $period->advance(); // move to period 2026-05
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $expected = new Money(0.00);
        $this->assertEquals($expected, $actual);

        $period->advance(); // move to period 2026-06
        $actual = self::$assetCollection->makeWithdrawals($period, $expense);
        $expected = new Money(50.00);
        $this->assertEquals($expected, $actual);

        /** @var Asset $asset1 */
        $asset1 = $assets[0];
        $this->assertEquals(0.00, $asset1->currentBalance()->value());

        /** @var Asset $asset2 */
        $asset2 = $assets[1];
        $this->assertEquals(0.00, $asset2->currentBalance()->value());

        /** @var Asset $asset3 */
        $asset3 = $assets[2];
        $this->assertEquals(0.00, $asset3->currentBalance()->value());

        /** @var Asset $asset3 */
        $asset4 = $assets[3];
        $this->assertEquals(150.00, $asset4->currentBalance()->value());
    }

    public function testEarnInterest(): void
    {
        $this->assertEquals(1, 1);
    }

}
