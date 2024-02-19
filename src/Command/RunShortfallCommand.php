<?php namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Service\Engine\Simulator;

#[AsCommand(name: 'app:shortfall')]
class RunShortfallCommand extends Command
{
    use BaseCommandTrait;

    public function __construct(Simulator $simulator, ?string $name = null)
    {
        $this->simulator = $simulator;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initSimulation();
        $response = $this->simulator->runShortfalls();
        return $this->processSimulation($response);
    }
}
