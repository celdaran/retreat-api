<?php namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use App\System\DatabaseReset;

#[AsCommand(name: 'app:database:hydrate')]
class HydrateDatabaseCommand extends Command
{
    private DatabaseReset $database;

    public function __construct(DatabaseReset $database, ?string $name = null)
    {
        $this->database = $database;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('test-data', 't', InputOption::VALUE_NONE, 'Include test data in reset')
            ->addOption('prod-data', 'p', InputOption::VALUE_NONE, 'Include production data in reset')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $includeSystemData = false;
        $includeTestData = $input->getOption('test-data');
        $includeProdData = $input->getOption('prod-data');

        $this->database->hydrate($includeSystemData, $includeTestData, $includeProdData);

        return Command::SUCCESS;
    }
}
