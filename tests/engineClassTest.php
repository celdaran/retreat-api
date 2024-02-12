<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Engine;
use App\Service\Engine\Until;
use App\Service\Reporting\Report;

final class engineClassTest extends TestCase
{
    private static Engine $engine;
    private static Until $until;

    /**
     * @throws Exception
     */
    public function testRunUntil300(): void
    {
        self::$engine = new Engine('ut05-expenses', 'ut05-assets', 'ut05-earnings');
        self::$until = new Until();

        self::$until->setPeriods(300);

        self::$engine->run(self::$until);

        $plan = $this->processOutput();

        $this->assertCount(300, $plan);
    }

    /**
     * @throws Exception
     */
    public function testRunUntilEmpty(): void
    {
        self::$engine = new Engine('ut05-expenses', 'ut05-assets', 'ut05-earnings');
        self::$until = new Until();

        self::$until->setUntil(Until::ASSETS_DEPLETE);

        self::$engine->run(self::$until);

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
