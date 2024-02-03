<?php namespace App\Service\Scenario;

use Exception;
use App\System\Log;
use App\System\Database;

class Scenario
{
    protected int $scenarioId;
    protected string $scenarioName;
    protected string $scenarioTable;

    private Database $data;
    private Log $log;

    public function __construct(Log $log)
    {
        $this->log = $log;

        try {
            $this->data = new Database($this->log);
            $this->data->connect($_ENV['DBHOST'], $_ENV['DBUSER'], $_ENV['DBPASS'], $_ENV['DBNAME']);
        } catch (Exception $e) {
            $this->log->warn($e->getMessage());
        }

    }

    public function id(): int
    {
        return $this->scenarioId;
    }

    public function getData(): Database
    {
        return $this->data;
    }

    public function getLog(): Log
    {
        return $this->log;
    }

    protected function fetchScenarioId(string $scenarioName, int $accountTypeId): int
    {
        $sql = "
          SELECT scenario_id 
          FROM scenario 
          WHERE scenario_name = :scenarioName
            AND account_type_id = :accountTypeId";
        $rows = $this->data->select($sql,
            ['scenarioName' => $scenarioName, 'accountTypeId' => $accountTypeId]);
        if (count($rows) === 1) {
            return (int)$rows[0]['scenario_id'];
        } else {
            return -1;
        }
    }

    public function clone(int $oldScenarioId, string $newScenarioName, string $newScenarioDescr, int $newAccountType)
    {
        // Create new scenario
        $sql = "INSERT INTO scenario (scenario_name, scenario_descr, account_type_id) VALUES (:newScenarioName, :newScenarioDescr, :newAccountType)";
        $this->data->exec($sql,
            ['newScenarioName' => $newScenarioName, 'newScenarioDescr' => $newScenarioDescr, 'newAccountType' => $newAccountType]);

        // Fetch new scenario ID
        $newScenarioId = $this->data->lastInsertId();

        // Clone scenario
        $sql = "
            INSERT INTO expense (scenario_id, expense_name, expense_descr, amount, inflation_rate, begin_year, begin_month, end_year, end_month, repeat_every)
            SELECT :newScenarioId, expense_name, expense_descr, amount, inflation_rate, begin_year, begin_month, end_year, end_month, repeat_every
            FROM expense
            WHERE scenario_id = :oldScenarioId
        ";
        $this->data->exec($sql, ['oldScenarioId' => $oldScenarioId, 'newScenarioId' => $newScenarioId]);
    }

    public function delete(): bool
    {
        $sql = sprintf("DELETE FROM %s WHERE scenario_id = %d", $this->scenarioTable, $this->scenarioId);
        $this->data->exec($sql);

        $sql = sprintf("DELETE FROM scenario WHERE scenario_id = %d", $this->scenarioId);
        $this->data->exec($sql);
        return true;
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
