<?php

require_once './restler.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Luracast\Restler\Restler;

$r = new Restler();
$r->addAPIClass('App\Api\Data');
$r->addAPIClass('App\Api\Say');
$r->addAPIClass('App\Api\Scenario');
$r->addAPIClass('App\Api\Simulation');
$r->handle();
