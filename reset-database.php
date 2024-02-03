<?php

require_once 'vendor/autoload.php';

use App\System\LogFactory;
use App\System\Database;
use App\System\DatabaseReset;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$log = LogFactory::getLogger();
$db = new Database($log);
$db->connect($_ENV['DBHOST'], $_ENV['DBUSER'], $_ENV['DBPASS'], $_ENV['DBNAME']);

$dbr = new DatabaseReset($db);
$dbr->reset();
