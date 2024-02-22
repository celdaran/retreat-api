<?php namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\Engine\Engine;

use App\Service\Engine\Simulator;

class SimulationController
{
    /** @var Simulator */
    private Simulator $simulator;

    public function __construct(Engine $engine)
    {
        $this->simulator = new Simulator($engine);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @noinspection PhpUnused
     * @api
     */
    #[Route('/asset/depletion', methods: ['POST'])]
    public function assetDepletion(Request $request): JsonResponse
    {
        $this->simulator->setParametersFromRequest($request);
        $simulatorResponse = $this->simulator->runAssetDepletion();
        return new JsonResponse($simulatorResponse->getPayload());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    #[Route('/shortfalls', methods: ['POST'])]
    public function shortfalls(Request $request): JsonResponse
    {
        $this->simulator->setParametersFromRequest($request);
        $simulatorResponse = $this->simulator->runShortfalls();
        return new JsonResponse($simulatorResponse->getPayload());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    #[Route('/table', methods: ['POST'])]
    public function table(Request $request): JsonResponse
    {
        $this->simulator->setParametersFromRequest($request);
        $simulatorResponse = $this->simulator->runTable();
        return new JsonResponse($simulatorResponse->getPayload());
    }


}
