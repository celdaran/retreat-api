<?php namespace App\Controller;

use Exception;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\Engine\Engine;
use App\Service\Engine\Until;

class Simulation
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/summary', methods: ['POST'])]
    public function summary(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        $expense = $body['expense'];
        $asset = $body['asset'];
        $earnings = $body['earnings'];
        $periods = $body['periods'];
        $startYear = $body['startYear'];
        $startMonth = $body['startMonth'];

        $payload = [];

        try {
            $plan = $this->getSimulation($expense, $asset, $earnings, $periods, $startYear, $startMonth);
            foreach ($plan->getSimulation() as $period) {
                $payload[] = [
                    "x" => sprintf("%04d-%02d", $period["year"], $period["month"]),
                    "y" => $period["shortfall"],
                ];
            }
        } catch (Exception $e) {
            $payload = [
                'code' => 500,
                'message' => $e->getMessage(),
            ];
        }

        return new JsonResponse($payload);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/asset/depletion', methods: ['POST'])]
    public function assetDepletion(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        $expense = $body['expense'];
        $asset = $body['asset'];
        $earnings = $body['earnings'];
        $periods = $body['periods'];
        $startYear = $body['startYear'];
        $startMonth = $body['startMonth'];

        // Generate payload template
        $payload = [
            'dataset' => [
                'source' => [
                ],
            ],
            'tooltip' => [
                "trigger" => "axis",
                "axisPointer" => [
                    "type" => "shadow"
                ]
            ],
            'title' => [
                "text" => "Asset Depletion",
                "left" => "center",
            ],
            'legend' => [
                "top" => 40,
                "type" => "scroll",
            ],
            'grid' => [
                "left" => 15,
                "right" => 15,
                "bottom" => 30,
                "top" => 100,
                "containLabel" => true
            ],
            'xAxis' => [
                "type" => "category"
            ],
            'yAxis' => [
                "type" => "value"
            ],
            'series' => [],
        ];

        $response = new Response();

        // Fill in data source entries
        try {
            $assetList = [];
            $first = true;

            $response = $this->getSimulation($expense, $asset, $earnings, $periods, $startYear, $startMonth);
            foreach ($response->getSimulation() as $period) {

                $entry = [
                    sprintf("%04d-%02d", $period["year"], $period["month"]),
                ];

                foreach ($period['assets'] as $name => $value) {
                    $entry[] = $value;
                    if ($first) {
                        $assetList[] = $name;
                    }
                }

                if ($first) {
                    $header = ["period"];
                    foreach ($assetList as $asset) {
                        $header[] = $asset;
                    }
                    $payload['dataset']['source'][] = $header;
                    $first = false;
                }

                $payload['dataset']['source'][] = $entry;
            }

            // Lastly define series
            for ($i = 0; $i < count($assetList); $i++) {
                $payload['series'][] = [
                    "type" => "bar",
                    "stack" => "Asset",
                ];
            }
        } catch (Exception $e) {
            $payload = [
                'code' => 500,
                'message' => $e->getMessage(),
            ];
        }

        $response->setPayload($payload);

        return new JsonResponse($response->getPayload());
    }

    /**
     * getPlan
     *
     * This method instantiates the engine, executes a plan, and returns the results.
     *
     * @param string $expense
     * @param string $asset
     * @param string $earnings
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @return Response
     * @throws Exception
     */
    private function getSimulation(
        string $expense,
        string $asset,
        string $earnings,
        string $periods,
        string $startYear,
        string $startMonth
    ): Response {

        $engine = new Engine(
            $expense,
            $asset,
            $earnings
        );
        $until = new Until();
        $until->setPeriods($periods);

        if (!$engine->run($until, $startYear, $startMonth)) {
            return new Response();
        }

        $simulation = $engine->getPlan();
        $logs = $engine->getLogs();
        $audit = $engine->getAudit();

        return new Response(true, $simulation, $logs, $audit);
    }

}
