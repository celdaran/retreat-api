<?php namespace App\Service\Data;

use App\Service\Log;

class Scenario
{
    private Database $data;
    private Log $log;

    public function __construct()
    {
        $this->log = new Log();
        $this->log->setLevel($_ENV['LOG_LEVEL']);

        try {
            $this->data = new Database();
            $this->data->connect($_ENV['DBHOST'], $_ENV['DBUSER'], $_ENV['DBPASS'], $_ENV['DBNAME']);
        } catch (\Exception $e) {
            $this->log->warn($e->getMessage());
        }

    }

    public function getData(): Database
    {
        return $this->data;
    }

    public function getLog(): Log
    {
        return $this->log;
    }

    protected function getRowsForScenario(string $scenarioName, string $scenarioType, string $sql): array
    {
        // Get the data
        $rows = $this->data->select($sql, ['scenario_name' => $scenarioName]);

        if (count($rows) === 0) {
            $this->getLog()->info('Scenario "' . $scenarioName . '" not found for ' . $scenarioType);
        }

        return $rows;
    }

}
