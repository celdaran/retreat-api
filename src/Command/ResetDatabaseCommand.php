<?php namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\System\LogFactory;
use App\System\Database;
use App\System\DatabaseReset;

#[AsCommand(name: 'app:database:reset')]
class ResetDatabaseCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $log = LogFactory::getLogger();
        $db = new Database($log);
        $db->connect($_ENV['DBHOST'], $_ENV['DBUSER'], $_ENV['DBPASS'], $_ENV['DBNAME']);

        $dbr = new DatabaseReset($db);
        $dbr->reset();

        return Command::SUCCESS;
    }
}
