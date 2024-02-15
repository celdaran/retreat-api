<?php namespace App\Command;

use App\Service\Engine\Simulator;
use App\Service\Engine\SimulatorResponse;
use App\Service\Reporting\Report;

trait BaseCommandTrait
{
    protected function initSimulation(): Simulator
    {
        $simulator = new Simulator();
        $simulator->setParameters(
            'Default',
            'Default',
            'Default',
            360,
            2025,
            1,
        );
        return $simulator;
    }

    protected function processSimulation(SimulatorResponse $response): int
    {
        if ($response->isSuccess()) {

            $simulation = $response->getSimulation();
            $logs = $response->getLog();

            $reporting = new Report();
            $report = $reporting->standard($simulation);

            $csvFileName = sprintf('simulation.%s.csv', date('Ymd-His'));
            $logFileName = sprintf('simulation.%s.log', date('Ymd-His'));

            file_put_contents($csvFileName, $report);
            file_put_contents($logFileName, join("", $logs));

            return 0;
        } else {
            $logFileName = sprintf('simulation.%s.err', date('Ymd-His'));
            $payload = $response->getPayload();
            file_put_contents($logFileName, $payload['message']);
            return 1;
        }
    }
}
