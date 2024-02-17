<?php namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\Scenario\Scenario as DataScenario;

class Scenario
{
    public DataScenario $scenario;

    public function __construct(DataScenario $scenario)
    {
        $this->scenario = $scenario;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    #[Route('/clone', methods: ['POST'])]
    public function clone(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        $newScenarioName = $body['newScenarioName'];
        $newScenarioDescr = $body['newScenarioDescr'];
        $oldScenarioId = $body['oldScenarioId'];

        $this->scenario->clone($oldScenarioId, $newScenarioName, $newScenarioDescr, 1);
        return new JsonResponse(['msg' => 'Probably succeeded...idk']);
    }

}
