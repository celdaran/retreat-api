<?php namespace App\Controller;

use App\System\LogFactory;
use App\Service\Scenario\Scenario as DataScenario;

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
        $log = LogFactory::getLogger();
        $scenario = new DataScenario($log);
        $scenario->clone($oldScenarioId, $newScenarioName, $newScenarioDescr, 1);
        return ['msg' => 'Probably succeeded. IDK'];
    }

}
