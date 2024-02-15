<?php namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:shortfall')]
class RunShortfallCommand extends Command
{
    use BaseCommandTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $simulator = $this->initSimulation();
        $response = $simulator->runShortfalls();
        return $this->processSimulation($response);
    }
}
