<?php

require_once './restler.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Luracast\Restler\Restler;

$r = new Restler();

try {
    $r->addAPIClass('App\Api\Scenario');
    $r->addAPIClass('App\Api\Simulation');
    $r->handle();
} catch (Exception $e) {
    echo json_encode(['error' => true]);
}
