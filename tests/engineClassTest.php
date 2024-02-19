<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Engine;
use App\Service\Engine\SimulationParameters;
use App\Service\Engine\Until;
use App\Service\Engine\IncomeCollection;
use App\Service\Scenario\ExpenseCollection;
use App\Service\Scenario\AssetCollection;
use App\Service\Scenario\EarningsCollection;
use App\System\Database;
use App\System\Log;

use App\Service\Reporting\Report;

final class engineClassTest extends TestCase
{
    private static Database $database;
    private static Engine $engine;
    private static Until $until;
    private static Log $log;

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        self::$log = new Log('DEBUG', 'MEMORY');
        self::$database = new Database(self::$log, $_ENV['DBHOST'], $_ENV['DBNAME'], $_ENV['DBUSER'], $_ENV['DBPASS']);

        $expenseCollection = new ExpenseCollection(self::$database, self::$log);
        $assetCollection = new AssetCollection(self::$database, self::$log);
        $earningsCollection = new EarningsCollection(self::$database, self::$log);
        $incomeCollection = new IncomeCollection(self::$log);

        self::$engine = new Engine($expenseCollection, $assetCollection, $earningsCollection, $incomeCollection, self::$log);
    }

    /**
     * @throws Exception
     */
    public function testRunUntil300(): void
    {
        self::$until = new Until();
        self::$until->setPeriods(300);
        $simulationParameters = new SimulationParameters(
            'ut05-expenses',
            'ut05-assets',
            'ut05-earnings',
            self::$until);

        self::$engine->run($simulationParameters);
        $plan = $this->processOutput();

        $this->assertCount(300, $plan);
    }

    /**
     * @throws Exception
     */
    public function testRunUntilEmpty(): void
    {
        self::$until = new Until();
        self::$until->setUntil(Until::ASSETS_DEPLETE);
        $simulationParameters = new SimulationParameters(
            'ut05-expenses',
            'ut05-assets',
            'ut05-earnings',
            self::$until);

        self::$engine->run($simulationParameters);
        $plan = $this->processOutput();

        $this->assertCount(6, $plan);
    }

    private function processOutput(): array
    {
        $plan = self::$engine->getSimulation();
        $logs = self::$engine->getLogs();

        $reporting = new Report();
        $report = $reporting->standard($plan);

        $csvFileName = sprintf('simulation.%s.csv', date('Ymd-His'));
        $logFileName = sprintf('simulation.%s.log', date('Ymd-His'));

        file_put_contents($csvFileName, $report);
        file_put_contents($logFileName, join("", $logs));

        return $plan;
    }

}
