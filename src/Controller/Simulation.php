<?php namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\Engine\Simulator;

class Simulation
{
    /**
     * @param Request $request
     * @param Simulator $simulator
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    #[Route('/shortfalls', methods: ['POST'])]
    public function shortfalls(Request $request, Simulator $simulator): JsonResponse
    {
        $simulator->setParametersFromRequest($request);
        $simulatorResponse = $simulator->runShortfalls();
        return new JsonResponse($simulatorResponse);
    }

    /**
     * @param Request $request
     * @param Simulator $simulator
     * @return JsonResponse
     * @noinspection PhpUnused
     * @api
     */
    #[Route('/asset/depletion', methods: ['POST'])]
    public function assetDepletion(Request $request, Simulator $simulator): JsonResponse
    {
        $simulator->setParametersFromRequest($request);
        $simulatorResponse = $simulator->runAssetDepletion();
        return new JsonResponse($simulatorResponse);
    }
}
