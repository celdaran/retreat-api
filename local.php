<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$api = new \App\Api\Simulation();

$results = $api->assetDepletion(
    'UNIT TEST 01',
    'UNIT TEST 01',
    'Default',
    12,
    2025,
    1,
);

print json_encode($results);

