<?php namespace App\Service\Scenario;

use App\System\Log;
use App\System\Database;

class Scenario
{
    private Database $data;
    private Log $log;

    public function __construct(Log $log)
    {
        $this->log = $log;

        try {
            $this->data = new Database($this->log);
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

    public function clone(int $oldScenarioId, string $newScenarioName, string $newScenarioDescr, int $newAccountType)
    {
        // Create new scenario
        $sql = "INSERT INTO scenario (scenario_name, scenario_descr, account_type_id) VALUES (:newScenarioName, :newScenarioDescr, :newAccountType)";
        $sth = $this->data->exec($sql, ['newScenarioName' => $newScenarioName, 'newScenarioDescr' => $newScenarioDescr, 'newAccountType' => $newAccountType]);
        $error = $this->data->lastError();

        // Fetch new scenario ID
        $newScenarioId = $this->data->lastInsertId();

        // Clone scenario
        $sql = "
            INSERT INTO expense (scenario_id, expense_name, expense_descr, amount, inflation_rate, begin_year, begin_month, end_year, end_month, repeat_every)
            SELECT :newScenarioId, expense_name, expense_descr, amount, inflation_rate, begin_year, begin_month, end_year, end_month, repeat_every
            FROM expense
            WHERE scenario_id = :oldScenarioId
        ";
        $sth = $this->data->exec($sql, ['oldScenarioId' => $oldScenarioId, 'newScenarioId' => $newScenarioId]);
        $error = $this->data->lastError();
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
