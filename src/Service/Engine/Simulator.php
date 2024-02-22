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
    public function runTable(): SimulatorResponse
    {
        $payload = [];

        try {
            $response = $this->runSimulation();
            foreach ($response->getSimulation() as $period) {
                $step = [];
                $step['period'] = $period['period'];
                $step['month'] = sprintf("%04d-%02d", $period["year"], $period["month"]);
                foreach ($period['expenses'] as $n => $v) {
                    $step[$n] = $v;
                }
                $step['expense'] = $period['expense'];
                $step['income'] = $period['income'];
                $step['incomeTax'] = $period['incomeTax'];
                foreach ($period['earnings'] as $n => $v) {
                    $step[$n] = $v;
                }
                $step['shortfall'] = $period['shortfall'];
                foreach ($period['assets'] as $n => $v) {
                    $step[$n] = $v;
                }
                $step['withdrawals'] = $period['withdrawals'];
                $payload[] = $step;
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
    public function runSummary(): SimulatorResponse
    {
        $payload = [];

        try {
            $response = $this->runSimulation();
            foreach ($response->getSummary() as $k => $v) {
                $payload[] = [
                    "Item" => $k,
                    "Value" => $v,
                ];
            };
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
            (int)$body['taxEngine'],
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
        int $startMonth,
        int $taxEngine)
    {
        $until = new Until();
        if ($periods > 0) {
            $until->setPeriods($periods);
        } else {
            $until->setUntil(Until::ASSETS_DEPLETE);
        }
        $this->simulationParameters = new SimulationParameters(
            $expense,
            $asset,
            $earnings,
            $until,
            $startYear,
            $startMonth,
            $taxEngine,
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
        $summary = $this->engine->getSummary();

        return new SimulatorResponse($success, $simulation, $logs, $audit, $summary);
    }

}
