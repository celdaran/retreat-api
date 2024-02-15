<?php namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:asset:depletion')]
class RunAssetDepletionCommand extends Command
{
    use BaseCommandTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $simulator = $this->initSimulation();
        $response = $simulator->runAssetDepletion();
        return $this->processSimulation($response);
    }
}
