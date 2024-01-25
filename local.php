<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$api = new \App\Api\Simulation();

$results = $api->summary(
    'UNIT TEST 01',
    'UNIT TEST 01',
    'Default',
    0.25,
    12,
    2025,
    1,
);

var_dump($results);
