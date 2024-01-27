<?php

require_once 'vendor/autoload.php';

use App\Api\Simulation;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$api = new Simulation();

$results = $api->assetDepletion(
    'UNIT TEST 01',
    'UNIT TEST 01',
    'Default',
    12,
    2025,
    1,
);

foreach ($results['logs'] as $log) {
    echo $log;
}
print json_encode($results['simulation']);
