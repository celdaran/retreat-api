<?php namespace App\Api;

use App\Service\Data\Scenario as DataScenario;

class Scenario
{
    /**
     * @url POST /clone
     * @param string $newScenarioName
     * @param string $newScenarioDescr
     * @param string $oldScenarioId
     * @return array
     */
    public function clone(string $newScenarioName, string $newScenarioDescr, string $oldScenarioId): array
    {
        $scenario = new DataScenario();
        $scenario->clone($oldScenarioId, $newScenarioName, $newScenarioDescr, 1);
        return ['msg' => 'Probably succeeded. IDK'];
    }

}