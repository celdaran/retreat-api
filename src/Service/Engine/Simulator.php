<?php namespace App\Service\Engine;

use Exception;
use Symfony\Component\HttpFoundation\Request;

class Simulator
{
    /** @var SimulationParameters */
    private SimulationParameters $simulationParameters;

    private Engine $engine;

    public function __construct(Engine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @return SimulatorResponse
     */
    public function runShortfalls(): SimulatorResponse
    {
        $payload = [];

        try {
            $response = $this->runSimulation();
            foreach ($response->getSimulation() as $period) {
                $payload[] = [
                    "x" => sprintf("%04d-%02d", $period["year"], $period["month"]),
                    "y" => $period["shortfall"],
                ];
            }
        } catch (Exception $e) {
            $response = new SimulatorResponse();
            $payload = [
                'code' => 500,
                'message' => $e->getMessage(),
            ];
        }

        $response->setPayload($payload);

        return $response;
    }

    /**
     * @return SimulatorResponse
     */
    public function runAssetDepletion(): SimulatorResponse
    {
        // Define payload template
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

        // Fill in data source entries
        try {
            $assetList = [];
            $first = true;

            $response = $this->runSimulation();
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
            $response = new SimulatorResponse();
            $payload = [
                'code' => 500,
                'message' => $e->getMessage(),
            ];
        }

        $response->setPayload($payload);

        return $response;
    }

    /**
     * @param Request $request
     */
    public function setParametersFromRequest(Request $request)
    {
        $body = json_decode($request->getContent(), true);
        $this->setParameters(
            $body['expense'],
            $body['asset'],
            $body['earnings'],
            (int)$body['periods'],
            (int)$body['startYear'],
            (int)$body['startMonth'],
        );
    }

    /**
     * @param string $expense
     * @param string $asset
     * @param string $earnings
     * @param int $periods
     * @param int $startYear
     * @param int $startMonth
     */
    public function setParameters(
        string $expense,
        string $asset,
        string $earnings,
        int $periods,
        int $startYear,
        int $startMonth)
    {
        $until = new Until();
        $until->setPeriods($periods);
        $this->simulationParameters = new SimulationParameters(
            $expense,
            $asset,
            $earnings,
            $until,
            $startYear,
            $startMonth,
        );
    }

    /**
     * runSimulation
     * This method instantiates the engine, runs a simulation, and returns the results.
     * @return SimulatorResponse
     * @throws Exception
     */
    private function runSimulation(): SimulatorResponse
    {
        $success = $this->engine->run($this->simulationParameters);

        $simulation = $this->engine->getSimulation();
        $logs = $this->engine->getLogs();
        $audit = $this->engine->getAudit();

        return new SimulatorResponse($success, $simulation, $logs, $audit);
    }

}
