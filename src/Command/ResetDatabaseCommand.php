<?php namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\System\DatabaseReset;

#[AsCommand(name: 'app:database:reset')]
class ResetDatabaseCommand extends Command
{
    private DatabaseReset $database;

    public function __construct(DatabaseReset $database, ?string $name = null)
    {
        $this->database = $database;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->database->reset($backupDataFirst);
        $this->database->hydrate(true, false, false);

        return Command::SUCCESS;
    }
}
