<?php

require_once 'vendor/autoload.php';

use App\Api\Simulation;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$api = new Simulation();

$response = $api->assetDepletion(
    'ut05-expenses',
    'ut05-assets',
    'ut05-earnings',
    240,
    2025,
    8,
);

/*
foreach ($results['logs'] as $log) {
    echo $log;
}
*/

/*
$log = implode("", $results['logs']);
file_put_contents('local.log', $log);
*/

echo "\n";
print json_encode($response->getPayload());
echo "\n";
