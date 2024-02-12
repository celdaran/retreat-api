<?php namespace App\Command;

use Symfony\Component\Console\Command\Command;

use App\Service\Engine\Simulator;
use App\Service\Engine\SimulatorResponse;
use App\Service\Reporting\Report;

class BaseCommand extends Command
{
    protected function initSimulation(): Simulator
    {
        $simulator = new Simulator();
        $simulator->setParameters(
            'ut05-expenses',
            'ut05-assets',
            'ut05-earnings',
            240,
            2025,
            8,
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

            return Command::SUCCESS;
        } else {
            $logFileName = sprintf('simulation.%s.err', date('Ymd-His'));
            $payload = $response->getPayload();
            file_put_contents($logFileName, $payload['message']);
            return Command::FAILURE;
        }
    }
}
