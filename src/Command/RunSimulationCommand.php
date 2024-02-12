<?php namespace App\Command;

use App\Service\Reporting\Report;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Service\Engine\Simulator;

#[AsCommand(name: 'app:run')]
class RunSimulationCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
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

        $response = $simulator->runAssetDepletion();

        $payload = $response->getPayload();

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
            $output->writeln($payload['message']);
            return Command::FAILURE;
        }
    }
}
