<?php namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\Engine\Simulator;

class Simulation
{
    /** @var Simulator */
    private Simulator $simulator;

    public function __construct()
    {
        $this->simulator = new Simulator();
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
        return new JsonResponse($simulatorResponse);
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
        return new JsonResponse($simulatorResponse);
    }
}
